"""
Manim Renderer - Simplified text-only whiteboard animations
Animates teaching points line by line without complex layouts
"""

import os
import subprocess
from config import Config


class ManimRenderer:

    # Font mapping per language script
    LANGUAGE_FONTS = {
        "en": "Patrick Hand", "hi": "Nirmala UI", "bn": "Nirmala UI",
        "ta": "Nirmala UI", "te": "Nirmala UI", "mr": "Nirmala UI",
        "gu": "Nirmala UI", "kn": "Nirmala UI", "ml": "Nirmala UI",
        "pa": "Nirmala UI", "ur": "Segoe UI", "ar": "Segoe UI",
        "ja": "Yu Gothic", "ko": "Malgun Gothic", "zh": "Microsoft YaHei",
        "ru": "Patrick Hand", "es": "Patrick Hand", "fr": "Patrick Hand", "de": "Patrick Hand",
        "pt": "Patrick Hand", "it": "Patrick Hand",
    }

    def __init__(self, output_dir: str = None, language: str = "en"):
        self.output_dir = output_dir or Config.TEMP_DIR
        self.font = self.LANGUAGE_FONTS.get(language, "Nirmala UI")

    def render_scene(
        self,
        scene_number: int,
        visual_description: str,
        narration: str,
        duration: float,
        key_concepts: list = None,
        elements: dict = None,
    ) -> str:
        """Render a single whiteboard scene as MP4 using Manim"""
        # Extract simple text lines from elements
        text_lines = self._extract_text_lines(visual_description, narration, elements or {})

        # Generate simplified Manim code
        scene_code = self._build_simple_scene(scene_number, text_lines, duration)

        scene_file = os.path.join(self.output_dir, f"scene_{scene_number}.py")
        with open(scene_file, "w", encoding="utf-8") as f:
            f.write(scene_code)

        output_file = self._run_manim(scene_file, f"WhiteboardScene{scene_number}", scene_number)

        if os.path.exists(scene_file):
            os.remove(scene_file)

        return output_file

    def _extract_text_lines(self, visual_description: str, narration: str, elements: dict) -> list:
        """Extract simple text teaching lines from scene data"""
        lines = []

        # Title first
        if elements.get("title_text"):
            lines.append(elements["title_text"])

        # Then bullet points
        if elements.get("bullet_points"):
            for bullet in elements["bullet_points"]:
                if isinstance(bullet, str) and len(bullet.strip()) > 2:
                    lines.append(bullet.strip())

        # Fallback: extract from visual description or narration
        if not lines:
            # Try splitting visual description
            for line in visual_description.split("\n"):
                line = line.strip().lstrip("-*•+ ")
                if 5 < len(line) < 120:
                    lines.append(line)

        # If still nothing, split narration into sentences
        if not lines:
            sentences = [s.strip() for s in narration.split(".") if 10 < len(s.strip()) < 100]
            lines = sentences[:6]

        # Limit to 8 lines max
        return lines[:8]

    def _build_simple_scene(self, scene_number: int, text_lines: list, duration: float) -> str:
        """Generate simple Manim code - just animate text lines one by one"""

        # Calculate timing
        total_time = max(duration, 3.0)
        per_line = total_time / max(len(text_lines), 1)
        write_time = min(per_line * 0.6, 2.5)
        wait_time = min(per_line * 0.3, 0.8)

        # Escape text for Python strings
        escaped_lines = [self._esc(line) for line in text_lines]

        # Build lines array
        lines_code = ",\n        ".join([f'"{line}"' for line in escaped_lines])

        return f'''from manim import *

STORY_LINES = [
        {lines_code}
    ]

class WhiteboardScene{scene_number}(Scene):
    def construct(self):
        # White background - clean whiteboard
        self.camera.background_color = WHITE

        for line in STORY_LINES:
            # Create text
            text = Text(
                line,
                font="{self.font}",
                font_size=42,
                color=BLACK,
                weight=NORMAL
            )
            text.move_to(ORIGIN)

            # Animate: write text, wait, then clear
            self.play(Write(text), run_time={write_time:.2f})
            self.wait({wait_time:.2f})
            self.clear()
'''

    def _esc(self, text: str) -> str:
        """Escape text for Python string literal"""
        return (
            str(text)
            .replace("\\", "\\\\")
            .replace('"', '\\"')
            .replace("'", "\\'")
            .replace("\n", " ")
            .strip()[:90]
        )

    def _run_manim(self, scene_file: str, scene_class: str, scene_number: int) -> str:
        """Execute Manim to render the scene as MP4"""
        output_file = os.path.join(self.output_dir, f"scene_{scene_number}.mp4")
        scene_dir = os.path.dirname(os.path.abspath(scene_file))
        scene_filename = os.path.basename(scene_file)
        media_dir = os.path.abspath(self.output_dir)

        cmd = [
            "manim", "render",
            "-qm",              # 720p, 30fps
            "--format", "mp4",
            "--media_dir", media_dir,
            "--disable_caching",
            scene_filename, scene_class,
        ]

        print(f"[MANIM] Rendering {scene_class}...")
        result = subprocess.run(cmd, capture_output=True, text=True, timeout=180, cwd=scene_dir)

        if result.stdout:
            print(f"[MANIM STDOUT] {result.stdout[-400:]}")
        if result.returncode != 0:
            print(f"[MANIM ERROR] {result.stderr[-600:]}")
            return self._generate_fallback(scene_number, output_file)

        # Find the actual rendered file in media dir
        expected_path = os.path.join(
            media_dir, "videos", scene_filename.replace(".py", ""),
            "720p30", f"{scene_class}.mp4"
        )

        if os.path.exists(expected_path):
            import shutil
            shutil.copy(expected_path, output_file)
            print(f"[MANIM] Success: {output_file}")
            return output_file

        print(f"[MANIM] Rendered file not found at {expected_path}")
        return self._generate_fallback(scene_number, output_file)

    def _generate_fallback(self, scene_number: int, output_file: str) -> str:
        """Generate a minimal fallback video if Manim fails"""
        print(f"[MANIM] Generating fallback for scene {scene_number}")

        # Create a minimal 2-second black video with ffmpeg
        cmd = [
            "ffmpeg", "-y",
            "-f", "lavfi", "-i", "color=c=white:s=1280x720:d=2",
            "-c:v", "libx264", "-pix_fmt", "yuv420p",
            output_file
        ]

        subprocess.run(cmd, capture_output=True, timeout=30)

        if os.path.exists(output_file):
            print(f"[MANIM] Fallback created: {output_file}")
            return output_file

        raise RuntimeError(f"Failed to generate scene {scene_number}")
