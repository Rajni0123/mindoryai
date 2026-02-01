"""
Video Stitcher - Combines Manim scene clips with Edge TTS audio into final MP4
Uses FFmpeg for muxing and concatenation
Supports burning narration subtitles into video
"""

import os
import subprocess
import re
import concurrent.futures
from config import Config


class VideoStitcher:

    def stitch(
        self,
        scenes: list,
        video_clips: dict,
        audio_paths: dict,
        output_path: str,
    ) -> float:
        """Stitch all scene clips + audio into a single final video with subtitles"""
        temp_dir = os.path.dirname(output_path)
        segments = []
        total_duration = 0.0

        def _process_segment(scene):
            sn = scene["scene_number"]
            video_path = video_clips.get(sn)
            audio_data = audio_paths.get(sn, {})
            audio_path = audio_data.get("path") if isinstance(audio_data, dict) else None
            audio_duration = audio_data.get("duration", scene.get("duration", 10)) if isinstance(audio_data, dict) else 10
            narration = scene.get("narration", "")
            segment_path = os.path.join(temp_dir, f"segment_{sn}.mp4")

            if video_path and os.path.exists(video_path) and audio_path and os.path.exists(audio_path):
                self._mux_video_audio(video_path, audio_path, segment_path, audio_duration, narration)
            elif video_path and os.path.exists(video_path):
                self._extend_video(video_path, segment_path, audio_duration)
            elif audio_path and os.path.exists(audio_path):
                self._audio_to_video(audio_path, segment_path, audio_duration, narration)
            else:
                self._generate_blank(segment_path, scene.get("duration", 10))
                audio_duration = scene.get("duration", 10)

            return sn, segment_path, audio_duration

        # Process all segments in parallel (FFmpeg mux per scene)
        with concurrent.futures.ThreadPoolExecutor(max_workers=min(len(scenes), 4)) as pool:
            futures = [pool.submit(_process_segment, s) for s in scenes]
            results = []
            for future in concurrent.futures.as_completed(futures):
                results.append(future.result())

        # Sort by scene number to maintain order
        results.sort(key=lambda x: x[0])
        for sn, segment_path, audio_duration in results:
            if os.path.exists(segment_path):
                segments.append(segment_path)
                total_duration += audio_duration

        if not segments:
            raise Exception("No segments generated")

        # Concatenate all segments
        self._concatenate(segments, output_path)

        # Cleanup segments
        for seg in segments:
            if os.path.exists(seg):
                os.remove(seg)

        return total_duration

    def _generate_srt(self, narration: str, duration: float, srt_path: str) -> str:
        """Generate SRT subtitle file from narration text.
        Splits narration into chunks of ~6-8 words, distributed across the duration."""
        if not narration or not narration.strip():
            return None

        # Clean narration text
        text = narration.strip()
        # Split into words
        words = text.split()
        if not words:
            return None

        # Group words into subtitle chunks (6-8 words each)
        chunk_size = 7
        chunks = []
        for i in range(0, len(words), chunk_size):
            chunk = " ".join(words[i:i + chunk_size])
            chunks.append(chunk)

        if not chunks:
            return None

        # Distribute chunks evenly across duration
        time_per_chunk = duration / len(chunks)

        srt_content = ""
        for i, chunk in enumerate(chunks):
            start_time = i * time_per_chunk
            end_time = min((i + 1) * time_per_chunk, duration)

            # Format as SRT timestamps: HH:MM:SS,mmm
            start_str = self._format_srt_time(start_time)
            end_str = self._format_srt_time(end_time)

            srt_content += f"{i + 1}\n{start_str} --> {end_str}\n{chunk}\n\n"

        with open(srt_path, "w", encoding="utf-8") as f:
            f.write(srt_content)

        print(f"[SUBTITLE] Generated SRT: {len(chunks)} subtitles for {duration:.1f}s")
        return srt_path

    def _format_srt_time(self, seconds: float) -> str:
        """Format seconds as SRT timestamp: HH:MM:SS,mmm"""
        hours = int(seconds // 3600)
        minutes = int((seconds % 3600) // 60)
        secs = int(seconds % 60)
        millis = int((seconds % 1) * 1000)
        return f"{hours:02d}:{minutes:02d}:{secs:02d},{millis:03d}"

    def _get_subtitle_filter(self, srt_path: str) -> str:
        """Build FFmpeg subtitles filter string.
        Uses ASS/SSA style for better formatting control."""
        # Escape path for FFmpeg filter (need to escape backslashes and colons on Windows)
        escaped_path = srt_path.replace("\\", "/").replace(":", "\\:")
        # Style: white text with black outline, positioned at bottom
        return (
            f"subtitles='{escaped_path}'"
            f":force_style='FontName=Arial,FontSize=14,PrimaryColour=&HFFFFFF&,"
            f"OutlineColour=&H000000&,Outline=1,Shadow=0,Alignment=2,"
            f"MarginV=20,Bold=0'"
        )

    def _mux_video_audio(self, video_path: str, audio_path: str, output_path: str, duration: float, narration: str = ""):
        """Combine video with audio and burn subtitles. If video is shorter, freeze last frame."""
        # Generate SRT file for subtitles
        srt_path = output_path + ".srt"
        has_subs = bool(narration and self._generate_srt(narration, duration, srt_path))

        # Build video filter chain
        vf_parts = [
            "scale=1280:720:force_original_aspect_ratio=decrease",
            "pad=1280:720:(ow-iw)/2:(oh-ih)/2:white",
            "fps=30",
            "tpad=stop_mode=clone:stop_duration=5",
        ]

        # Add subtitle filter if SRT was generated
        if has_subs and os.path.exists(srt_path):
            sub_filter = self._get_subtitle_filter(srt_path)
            vf_parts.append(sub_filter)

        vf = ",".join(vf_parts)

        cmd = [
            Config.FFMPEG_PATH, "-y",
            "-i", video_path,
            "-i", audio_path,
            "-c:v", "libx264",
            "-c:a", "aac",
            "-b:a", "128k",
            "-pix_fmt", "yuv420p",
            "-vf", vf,
            "-movflags", "+faststart",
            "-shortest",
            output_path,
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=120)

        if result.returncode != 0:
            print(f"[STITCH] Mux with subs failed: {result.stderr[:300]}")
            # Fallback: try without subtitles
            if has_subs:
                print("[STITCH] Retrying without subtitles...")
                vf_nosub = ",".join(vf_parts[:-1])  # Remove subtitle filter
                cmd_nosub = [
                    Config.FFMPEG_PATH, "-y",
                    "-i", video_path,
                    "-i", audio_path,
                    "-c:v", "libx264",
                    "-c:a", "aac",
                    "-b:a", "128k",
                    "-pix_fmt", "yuv420p",
                    "-vf", vf_nosub,
                    "-movflags", "+faststart",
                    "-shortest",
                    output_path,
                ]
                subprocess.run(cmd_nosub, capture_output=True, timeout=120)

        # Cleanup SRT
        if os.path.exists(srt_path):
            os.remove(srt_path)

    def _extend_video(self, video_path: str, output_path: str, duration: float):
        """Extend/trim video to match duration"""
        cmd = [
            Config.FFMPEG_PATH, "-y",
            "-i", video_path,
            "-t", str(duration),
            "-c:v", "libx264",
            "-pix_fmt", "yuv420p",
            "-vf", "scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2:white,fps=30",
            "-an",
            output_path,
        ]
        subprocess.run(cmd, capture_output=True, timeout=60)

    def _audio_to_video(self, audio_path: str, output_path: str, duration: float, narration: str = ""):
        """Generate white background video with audio and subtitles"""
        # Generate SRT file for subtitles
        srt_path = output_path + ".srt"
        has_subs = bool(narration and self._generate_srt(narration, duration, srt_path))

        vf_parts = []
        if has_subs and os.path.exists(srt_path):
            sub_filter = self._get_subtitle_filter(srt_path)
            vf_parts.append(sub_filter)

        cmd = [
            Config.FFMPEG_PATH, "-y",
            "-f", "lavfi",
            "-i", f"color=c=white:s=1280x720:d={duration}:r=30",
            "-i", audio_path,
            "-c:v", "libx264",
            "-c:a", "aac",
            "-b:a", "128k",
            "-shortest",
            "-pix_fmt", "yuv420p",
        ]

        if vf_parts:
            cmd.extend(["-vf", ",".join(vf_parts)])

        cmd.append(output_path)

        result = subprocess.run(cmd, capture_output=True, text=True, timeout=60)

        if result.returncode != 0 and has_subs:
            print(f"[STITCH] Audio-to-video with subs failed, retrying without: {result.stderr[:200]}")
            cmd_nosub = [
                Config.FFMPEG_PATH, "-y",
                "-f", "lavfi",
                "-i", f"color=c=white:s=1280x720:d={duration}:r=30",
                "-i", audio_path,
                "-c:v", "libx264",
                "-c:a", "aac",
                "-b:a", "128k",
                "-shortest",
                "-pix_fmt", "yuv420p",
                output_path,
            ]
            subprocess.run(cmd_nosub, capture_output=True, timeout=60)

        # Cleanup SRT
        if os.path.exists(srt_path):
            os.remove(srt_path)

    def _generate_blank(self, output_path: str, duration: float):
        """Generate a blank white video"""
        cmd = [
            Config.FFMPEG_PATH, "-y",
            "-f", "lavfi",
            "-i", f"color=c=white:s=1280x720:d={duration}:r=30",
            "-c:v", "libx264",
            "-pix_fmt", "yuv420p",
            output_path,
        ]
        subprocess.run(cmd, capture_output=True, timeout=30)

    def _concatenate(self, segments: list, output_path: str):
        """Concatenate video segments using FFmpeg concat demuxer"""
        concat_file = output_path + ".concat.txt"
        with open(concat_file, "w") as f:
            for seg in segments:
                abs_path = os.path.abspath(seg).replace("\\", "/")
                f.write(f"file '{abs_path}'\n")

        # Try stream copy first (much faster, no re-encoding)
        cmd = [
            Config.FFMPEG_PATH, "-y",
            "-f", "concat",
            "-safe", "0",
            "-i", concat_file,
            "-c", "copy",
            "-movflags", "+faststart",
            output_path,
        ]
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=300)

        # Fallback to re-encode if stream copy fails
        if result.returncode != 0:
            print(f"[STITCH] Stream copy failed, re-encoding...")
            cmd = [
                Config.FFMPEG_PATH, "-y",
                "-f", "concat",
                "-safe", "0",
                "-i", concat_file,
                "-c:v", "libx264",
                "-c:a", "aac",
                "-b:a", "128k",
                "-pix_fmt", "yuv420p",
                "-movflags", "+faststart",
                output_path,
            ]
            result = subprocess.run(cmd, capture_output=True, text=True, timeout=300)

        if result.returncode != 0:
            raise Exception(f"FFmpeg concat failed: {result.stderr[:500]}")

        if os.path.exists(concat_file):
            os.remove(concat_file)

    def generate_thumbnail(self, video_path: str, thumbnail_path: str):
        """Extract thumbnail from video at 1 second"""
        cmd = [
            Config.FFMPEG_PATH, "-y",
            "-i", video_path,
            "-ss", "1",
            "-vframes", "1",
            "-vf", "scale=640:360",
            "-q:v", "2",
            thumbnail_path,
        ]
        subprocess.run(cmd, capture_output=True, timeout=15)
