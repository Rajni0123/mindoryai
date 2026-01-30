"""
Video Stitcher - Combines Manim scene clips with Edge TTS audio into final MP4
Uses FFmpeg for muxing and concatenation
"""

import os
import subprocess
from config import Config


class VideoStitcher:

    def stitch(
        self,
        scenes: list,
        video_clips: dict,
        audio_paths: dict,
        output_path: str,
    ) -> float:
        """Stitch all scene clips + audio into a single final video"""
        temp_dir = os.path.dirname(output_path)
        segments = []
        total_duration = 0.0

        for scene in scenes:
            sn = scene["scene_number"]
            video_path = video_clips.get(sn)
            audio_data = audio_paths.get(sn, {})
            audio_path = audio_data.get("path") if isinstance(audio_data, dict) else None
            audio_duration = audio_data.get("duration", scene.get("duration", 10)) if isinstance(audio_data, dict) else 10

            segment_path = os.path.join(temp_dir, f"segment_{sn}.mp4")

            if video_path and os.path.exists(video_path) and audio_path and os.path.exists(audio_path):
                # Mux Manim video with Edge TTS audio
                self._mux_video_audio(video_path, audio_path, segment_path, audio_duration)
            elif video_path and os.path.exists(video_path):
                # Video only - extend to match expected duration
                self._extend_video(video_path, segment_path, audio_duration)
            elif audio_path and os.path.exists(audio_path):
                # Audio only - generate black/white video
                self._audio_to_video(audio_path, segment_path, audio_duration)
            else:
                # No assets - generate placeholder
                self._generate_blank(segment_path, scene.get("duration", 10))
                audio_duration = scene.get("duration", 10)

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

    def _mux_video_audio(self, video_path: str, audio_path: str, output_path: str, duration: float):
        """Combine video with audio. If video is shorter, freeze last frame (no loop/replay)."""
        # Use tpad to freeze last frame instead of -stream_loop which replays the animation
        cmd = [
            Config.FFMPEG_PATH, "-y",
            "-i", video_path,
            "-i", audio_path,
            "-c:v", "libx264",
            "-c:a", "aac",
            "-b:a", "128k",
            "-pix_fmt", "yuv420p",
            "-vf", f"scale=1280:720:force_original_aspect_ratio=decrease,pad=1280:720:(ow-iw)/2:(oh-ih)/2:white,fps=30,tpad=stop_mode=clone:stop_duration=120",
            "-movflags", "+faststart",
            "-shortest",
            output_path,
        ]
        subprocess.run(cmd, capture_output=True, timeout=120)

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

    def _audio_to_video(self, audio_path: str, output_path: str, duration: float):
        """Generate white background video with audio"""
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
            output_path,
        ]
        subprocess.run(cmd, capture_output=True, timeout=60)

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
                f.write(f"file '{os.path.abspath(seg)}'\n")

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
