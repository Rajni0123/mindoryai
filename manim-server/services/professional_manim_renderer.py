"""
Professional Manim Renderer - Multi-image whiteboard animations.
ALL images generated in real-time by AI. No static files.

Layout per scene (MULTI-IMAGE GRID):
  1. Title at top (handwritten header)
  2. Multiple SMALLER images arranged in a grid on ONE screen
  3. Arrow connectors between images to show flow/relationship
  4. Minimal text — only key labels, no paragraphs
  5. Images appear one by one with highlight, staying on screen
  Like a real teacher's whiteboard with multiple diagrams visible.
"""

import os
import subprocess
import textwrap
import concurrent.futures
from config import Config
from services.ai_image_generator import AIImageGenerator


class ProfessionalManimRenderer:

    # Title font (handwritten style)
    TITLE_FONTS = {
        "en": "Patrick Hand",
        "hi": "Nirmala UI",
        "bn": "Nirmala UI",
        "ta": "Nirmala UI",
        "te": "Nirmala UI",
        "mr": "Nirmala UI",
        "gu": "Nirmala UI",
        "kn": "Nirmala UI",
        "ml": "Nirmala UI",
        "pa": "Nirmala UI",
        "ur": "Segoe UI",
        "ar": "Segoe UI",
        "ja": "Yu Gothic",
        "ko": "Malgun Gothic",
        "zh": "Microsoft YaHei",
        "ru": "Patrick Hand",
        "es": "Patrick Hand",
        "fr": "Patrick Hand",
        "de": "Patrick Hand",
        "pt": "Patrick Hand",
        "it": "Patrick Hand",
    }

    # Body text font (clean, readable)
    BODY_FONTS = {
        "en": "Arial",
        "hi": "Nirmala UI",
        "bn": "Nirmala UI",
        "ta": "Nirmala UI",
        "te": "Nirmala UI",
        "mr": "Nirmala UI",
        "gu": "Nirmala UI",
        "kn": "Nirmala UI",
        "ml": "Nirmala UI",
        "pa": "Nirmala UI",
        "ur": "Segoe UI",
        "ar": "Segoe UI",
        "ja": "Yu Gothic",
        "ko": "Malgun Gothic",
        "zh": "Microsoft YaHei",
        "ru": "Arial",
        "es": "Arial",
        "fr": "Arial",
        "de": "Arial",
        "pt": "Arial",
        "it": "Arial",
    }

    def __init__(self, output_dir: str = None, gemini_api_key: str = None, gemini_model: str = None, language: str = "en", image_settings: dict = None):
        self.output_dir = os.path.abspath(output_dir or Config.TEMP_DIR)
        self.image_settings = image_settings or {}
        self.img_gen = AIImageGenerator(
            api_key=gemini_api_key,
            image_settings=self.image_settings,
        )
        self.language = language
        self.title_font = self.TITLE_FONTS.get(language, "Nirmala UI")
        self.body_font = self.BODY_FONTS.get(language, "Nirmala UI")

    def render_scene(
        self,
        scene_number: int,
        visual_description: str,
        narration: str,
        duration: float,
        key_concepts: list = None,
        elements: dict = None,
    ) -> str:
        title = elements.get("title_text", "") if elements else ""
        concepts = key_concepts or []

        img_dir = os.path.join(self.output_dir, "ai_assets")
        os.makedirs(img_dir, exist_ok=True)

        # Generate images for key concepts IN PARALLEL (max 2 per scene for speed)
        concept_images = []
        concepts_to_use = concepts[:2]

        def _gen_img(i, concept_text):
            clean = str(concept_text).strip()
            if len(clean) < 2:
                return None
            path = os.path.join(img_dir, f"scene_{scene_number}_c_{i}.png")
            result = self.img_gen.generate_concept_image(
                concept=clean,
                scene_title=title or f"Scene {scene_number}",
                description=visual_description,
                output_path=path,
            )
            return result if result and os.path.exists(str(result)) else None

        with concurrent.futures.ThreadPoolExecutor(max_workers=2) as pool:
            futures = {pool.submit(_gen_img, i, c): i for i, c in enumerate(concepts_to_use)}
            results = {}
            for future in concurrent.futures.as_completed(futures):
                idx = futures[future]
                img = future.result()
                if img:
                    results[idx] = img
            # Maintain order
            for i in sorted(results.keys()):
                concept_images.append(results[i])

        # Fallback: generate one scene-level image if no concept images
        if not concept_images:
            fallback_path = os.path.join(img_dir, f"scene_{scene_number}.png")
            fallback_img = self.img_gen.generate_scene_image(
                title=title or f"Scene {scene_number}",
                description=visual_description,
                concepts=concepts,
                output_path=fallback_path,
            )
            if fallback_img and os.path.exists(str(fallback_img)):
                concept_images = [fallback_img]

        # Build Manim scene
        scene_code = self._build_scene(
            scene_number=scene_number,
            title=title,
            concept_images=concept_images,
            duration=duration,
            narration=narration,
        )

        os.makedirs(self.output_dir, exist_ok=True)
        scene_file = os.path.join(self.output_dir, f"scene_{scene_number}.py")
        with open(scene_file, "w", encoding="utf-8") as f:
            f.write(scene_code)

        output_file = self._run_manim(scene_file, f"ProScene{scene_number}", scene_number)

        if os.path.exists(scene_file):
            os.remove(scene_file)

        return output_file

    def _build_scene(self, scene_number, title, concept_images, duration, narration=""):
        title_esc = self._esc(title)

        # Auto font size for title
        title_len = len(title_esc)
        if title_len > 35:
            title_font_size = 30
        elif title_len > 25:
            title_font_size = 34
        else:
            title_font_size = 38

        total = max(duration, 5.0)
        n_images = len(concept_images)

        # ── Extract SHORT labels from narration (1-2 word captions only) ──
        narration_clean = self._esc_long(narration)
        raw_lines = textwrap.wrap(narration_clean, width=35)
        captions = raw_lines[:n_images] if n_images > 0 else raw_lines[:3]

        # ── Time allocation ──
        title_time = 0.4
        content_time = total - title_time - 0.2
        hold_time = max(content_time * 0.15, 0.8)
        anim_time = content_time - hold_time

        # ── MULTI-IMAGE GRID LAYOUT ──
        # All images visible on ONE screen simultaneously
        # Grid positions depend on image count:
        #   1 image:  centered
        #   2 images: side by side
        #   3 images: row of 3
        #   4 images: 2x2 grid

        if n_images <= 1:
            positions = [(0.0, 0.0)]
            img_max_w, img_max_h = 5.5, 3.2
        elif n_images == 2:
            positions = [(-3.2, 0.0), (3.2, 0.0)]
            img_max_w, img_max_h = 4.5, 3.0
        elif n_images == 3:
            positions = [(-4.2, 0.0), (0.0, 0.0), (4.2, 0.0)]
            img_max_w, img_max_h = 3.5, 2.8
        else:  # 4+
            positions = [(-3.2, 1.2), (3.2, 1.2), (-3.2, -1.8), (3.2, -1.8)]
            img_max_w, img_max_h = 4.0, 2.2

        # ── Build image definitions (no outline rectangle) ──
        img_defs = ""
        for i, img_path in enumerate(concept_images[:min(n_images, 4)]):
            img_abs = os.path.abspath(img_path).replace("\\", "/")
            px, py = positions[i] if i < len(positions) else (0.0, 0.0)

            img_defs += f"""
        cimg_{i} = None
        try:
            cimg_{i} = ImageMobject(r"{img_abs}")
            if cimg_{i}.width > 0 and cimg_{i}.height > 0:
                s = min({img_max_w} / cimg_{i}.width, {img_max_h} / cimg_{i}.height)
                cimg_{i}.scale(s)
            cimg_{i}.move_to([{px}, {py}, 0])
        except Exception:
            cimg_{i} = None
"""

        # ── Build animation: images appear one by one, ALL stay on screen ──
        anim_code = ""

        if n_images > 0:
            time_per_img = anim_time / n_images
            for i in range(min(n_images, 4)):
                fade_in_time = min(time_per_img * 0.4, 0.8)
                view_time = max(time_per_img - fade_in_time - 0.3, 0.3)

                px, py = positions[i] if i < len(positions) else (0.0, 0.0)

                # Fade in image (no outline rectangle)
                anim_code += f"""
        # ── Image {i+1} ──
        if cimg_{i}:
            self.play(FadeIn(cimg_{i}, shift=UP * 0.2), run_time={fade_in_time:.2f})
"""

                # Arrow connecting this image to the next one
                if i < min(n_images, 4) - 1:
                    nx, ny = positions[i + 1] if (i + 1) < len(positions) else (0.0, 0.0)
                    if n_images <= 3:
                        ax1 = px + img_max_w / 2 + 0.15
                        ax2 = nx - img_max_w / 2 - 0.15
                        anim_code += f"""
            arr_{i} = Arrow(
                start=[{ax1:.2f}, {py:.2f}, 0],
                end=[{ax2:.2f}, {ny:.2f}, 0],
                color=RED, stroke_width=3.5, max_tip_length_to_length_ratio=0.15
            )
            self.play(GrowArrow(arr_{i}), run_time=0.25)
"""
                    else:
                        if i == 0:
                            ax1, ay1 = px + img_max_w / 2 + 0.1, py
                            ax2, ay2 = nx - img_max_w / 2 - 0.1, ny
                        elif i == 1:
                            ax1, ay1 = px, py - img_max_h / 2 - 0.1
                            ax2, ay2 = positions[2][0], positions[2][1] + img_max_h / 2 + 0.1
                        else:
                            ax1, ay1 = px + img_max_w / 2 + 0.1, py
                            ax2, ay2 = nx - img_max_w / 2 - 0.1, ny
                        anim_code += f"""
            arr_{i} = Arrow(
                start=[{ax1:.2f}, {ay1:.2f}, 0],
                end=[{ax2:.2f}, {ay2:.2f}, 0],
                color=RED, stroke_width=3.5, max_tip_length_to_length_ratio=0.15
            )
            self.play(GrowArrow(arr_{i}), run_time=0.25)
"""

                anim_code += f"""
            self.wait({view_time:.2f})
"""

        else:
            # No images fallback: show bullet points (not paragraphs)
            bullet_items = captions[:5]
            time_per_item = anim_time / max(len(bullet_items), 1)
            for ci, cap in enumerate(bullet_items):
                cap_esc = cap.replace('"', '\\"').replace("'", "\\'")
                # Add bullet/number prefix
                prefix = f"{ci + 1}." if len(bullet_items) > 1 else "•"
                write_time = min(time_per_item * 0.4, 0.8)
                view_time = max(time_per_item - write_time - 0.2, 0.3)
                y_pos = 2.0 - ci * 0.9

                anim_code += f"""
        bp_{ci} = Text("{prefix} {cap_esc}", font="{self.body_font}", font_size=22, color=BLACK)
        bp_{ci}.move_to([0.0, {y_pos:.1f}, 0])
        if bp_{ci}.width > 11.0:
            bp_{ci}.scale(11.0 / bp_{ci}.width)
        # Left-align bullet points
        bp_{ci}.shift(LEFT * (6.0 - bp_{ci}.width / 2))
        self.play(FadeIn(bp_{ci}, shift=RIGHT * 0.3), run_time={write_time:.2f})
        self.wait({view_time:.2f})
"""

        # ── Detect math formulas ──
        formula_code = ""
        formulas = self._extract_formulas(narration)
        if formulas:
            for fi, formula_str in enumerate(formulas[:2]):
                fx = -3.0 + fi * 6.0 if len(formulas) > 1 else 0.0
                formula_esc = formula_str.replace('"', '\\\\"')
                formula_code += f"""
        # ── Math Formula {fi} ──
        try:
            formula_{fi} = MathTex(r"{formula_esc}", color=BLACK, font_size=36)
            formula_{fi}.move_to([{fx:.1f}, -3.2, 0])
            formula_box_{fi} = SurroundingRectangle(
                formula_{fi}, color=BLACK, stroke_width=2, buff=0.15, corner_radius=0.06
            )
            self.play(Create(formula_box_{fi}), Write(formula_{fi}), run_time=0.6)
        except Exception:
            pass
"""

        return f"""from manim import *
import os

class ProScene{scene_number}(Scene):
    def construct(self):
        self.camera.background_color = WHITE

        # ── Title with handwriting effect ──
        heading = Text("{title_esc}", font="{self.title_font}", font_size={title_font_size}, color=BLACK, weight=BOLD)
        heading.move_to([0.0, 3.5, 0])
        if heading.width > 13.0:
            heading.scale(13.0 / heading.width)
        underline = Line(
            start=heading.get_left() + DOWN * 0.22,
            end=heading.get_right() + DOWN * 0.22,
            color=BLACK, stroke_width=2.5
        )
        try:
            self.play(AddTextLetterByLetter(heading, time_per_char=0.03), run_time={title_time:.2f})
        except Exception:
            self.play(Write(heading), run_time={title_time:.2f})
        self.play(Create(underline), run_time=0.1)

        # ── Pre-define images ──
{img_defs}

        # ── Multi-image grid: all images visible, arrows connecting them ──
{anim_code}
{formula_code}

        # Hold final frame (all images visible)
        self.wait({hold_time:.2f})
"""

    def _extract_formulas(self, text: str) -> list:
        """Extract mathematical formulas from narration text.
        Looks for patterns like F=ma, E=mc², a²+b²=c², etc."""
        import re
        if not text:
            return []
        formulas = []
        # Match patterns: word=expression, e.g. F=ma, E=mc^2, PV=nRT
        for m in re.finditer(r'[A-Z][a-z]?\s*=\s*[A-Za-z0-9\s\*\+\-\^²³/()]+', text):
            f = m.group().strip()
            if 3 <= len(f) <= 40:
                # Convert to LaTeX-friendly
                f = f.replace('²', '^{2}').replace('³', '^{3}').replace('*', r' \times ')
                formulas.append(f)
        return formulas

    def _esc(self, text: str) -> str:
        if not text:
            return ""
        return (
            str(text)
            .replace("\\", "\\\\")
            .replace('"', '\\"')
            .replace("'", "\\'")
            .replace("\n", " ")
            .strip()[:100]
        )

    def _esc_long(self, text: str) -> str:
        """Escape narration text (longer limit for full narration)."""
        if not text:
            return ""
        return (
            str(text)
            .replace("\\", "\\\\")
            .replace('"', '\\"')
            .replace("'", "\\'")
            .replace("\n", " ")
            .strip()[:400]
        )

    def _run_manim(self, scene_file: str, scene_class: str, scene_number: int) -> str:
        output_file = os.path.join(self.output_dir, f"scene_{scene_number}.mp4")
        scene_dir = os.path.dirname(os.path.abspath(scene_file))
        scene_filename = os.path.basename(scene_file)
        media_dir = os.path.abspath(self.output_dir)

        cmd = [
            "manim", "render",
            "-ql",  # Low quality (480p15) for fast rendering - stitcher upscales to 720p30
            "--fps", "30",
            "--format", "mp4",
            "--media_dir", media_dir,
            "--disable_caching",
            scene_filename, scene_class,
        ]

        print(f"[PRO MANIM] Rendering {scene_class}...")
        result = subprocess.run(cmd, capture_output=True, text=True, encoding="utf-8", errors="replace", timeout=120, cwd=scene_dir)

        if result.stdout:
            print(f"[MANIM OUT] {result.stdout[-500:]}")
        if result.returncode != 0:
            print(f"[MANIM ERR] {result.stderr[-600:]}")
            return self._generate_fallback(scene_number, output_file)

        expected = os.path.join(
            media_dir, "videos", scene_filename.replace(".py", ""),
            "480p15", f"{scene_class}.mp4"
        )
        if os.path.exists(expected):
            import shutil
            shutil.copy(expected, output_file)
            print(f"[PRO MANIM] OK: {output_file}")
            return output_file

        import glob
        pattern = os.path.join(media_dir, "**", f"{scene_class}.mp4")
        found = glob.glob(pattern, recursive=True)
        if found:
            import shutil
            shutil.copy(found[0], output_file)
            return output_file

        return self._generate_fallback(scene_number, output_file)

    def _generate_fallback(self, scene_number: int, output_file: str) -> str:
        print(f"[PRO MANIM] Fallback for scene {scene_number}")
        cmd = [
            "ffmpeg", "-y",
            "-f", "lavfi", "-i", "color=c=white:s=1280x720:d=2",
            "-c:v", "libx264", "-pix_fmt", "yuv420p",
            output_file,
        ]
        subprocess.run(cmd, capture_output=True, timeout=30)
        if os.path.exists(output_file):
            return output_file
        raise RuntimeError(f"Failed to generate scene {scene_number}")
