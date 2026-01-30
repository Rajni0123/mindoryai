"""
🎬 PROFESSIONAL WHITEBOARD VIDEO SYSTEM
========================================
Creates VideoScribe/Doodly style videos with:
- Hand drawing images/icons
- AI-generated illustrations
- Diagrams and charts
- Professional animations

This is a COMPLETE replacement for basic text-only animations.
"""

import os
import json
import requests
import hashlib
from pathlib import Path
from typing import List, Dict, Optional
import base64

# ============================================
# CONFIGURATION
# ============================================

CONFIG = {
    # Directories
    "ASSETS_DIR": "assets",
    "ICONS_DIR": "assets/icons",
    "IMAGES_DIR": "assets/images",
    "HANDS_DIR": "assets/hands",
    "OUTPUT_DIR": "media/videos",
    "TEMP_DIR": "temp",
    
    # Image Generation
    "USE_AI_IMAGES": True,  # Use Gemini/DALL-E for custom images
    "GEMINI_API_KEY": os.getenv("GEMINI_API_KEY", ""),
    
    # Fallback SVG Icons API (free)
    "ICONS_API": "https://api.iconify.design",
    
    # Video Settings
    "RESOLUTION": (1920, 1080),
    "FPS": 30,
    "BACKGROUND_COLOR": "#FFFEF5",
}


# ============================================
# FREE SVG/ICON SOURCES
# ============================================

class IconLibrary:
    """
    Fetches professional SVG icons from free sources
    
    Sources:
    - Iconify (10,000+ free icons)
    - Flaticon (requires attribution)
    - Undraw (illustrations)
    - SVGRepo (free SVGs)
    """
    
    # Icon mappings for common educational topics
    ICON_MAPPINGS = {
        # Technology
        "ai": "carbon:ai",
        "artificial intelligence": "carbon:ai",
        "machine learning": "carbon:machine-learning",
        "computer": "carbon:laptop",
        "robot": "carbon:bot",
        "brain": "carbon:idea",
        "neural network": "carbon:network-3",
        "data": "carbon:data-base",
        "cloud": "carbon:cloud",
        "internet": "carbon:globe",
        "code": "carbon:code",
        "programming": "carbon:terminal",
        "algorithm": "carbon:flow",
        
        # Science
        "atom": "carbon:chemistry",
        "chemistry": "carbon:chemistry",
        "physics": "carbon:meter",
        "biology": "carbon:dna",
        "math": "carbon:calculator",
        "formula": "carbon:function",
        "experiment": "carbon:flask",
        "microscope": "carbon:view",
        
        # Education
        "book": "carbon:book",
        "student": "carbon:user",
        "teacher": "carbon:user-speaker",
        "school": "carbon:education",
        "exam": "carbon:document",
        "certificate": "carbon:certificate",
        "graduation": "carbon:education",
        
        # Business
        "chart": "carbon:chart-line",
        "graph": "carbon:chart-bar",
        "money": "carbon:currency-dollar",
        "growth": "carbon:growth",
        "target": "carbon:target",
        "team": "carbon:group",
        
        # Objects
        "car": "carbon:car",
        "phone": "carbon:phone",
        "home": "carbon:home",
        "light": "carbon:light",
        "settings": "carbon:settings",
        "search": "carbon:search",
        "check": "carbon:checkmark",
        "arrow": "carbon:arrow-right",
    }
    
    @classmethod
    def get_icon_for_topic(cls, topic: str) -> Optional[str]:
        """Get icon identifier for a topic"""
        topic_lower = topic.lower()
        
        # Direct match
        if topic_lower in cls.ICON_MAPPINGS:
            return cls.ICON_MAPPINGS[topic_lower]
        
        # Partial match
        for key, icon in cls.ICON_MAPPINGS.items():
            if key in topic_lower or topic_lower in key:
                return icon
        
        return None
    
    @classmethod
    def download_svg(cls, icon_id: str, save_path: str) -> bool:
        """Download SVG from Iconify API"""
        try:
            # Parse icon_id (format: "prefix:name")
            prefix, name = icon_id.split(":")
            url = f"https://api.iconify.design/{prefix}/{name}.svg"
            
            response = requests.get(url, timeout=10)
            if response.status_code == 200:
                # Save SVG
                Path(save_path).parent.mkdir(parents=True, exist_ok=True)
                with open(save_path, 'w') as f:
                    f.write(response.text)
                return True
        except Exception as e:
            print(f"Error downloading icon: {e}")
        
        return False


# ============================================
# AI IMAGE GENERATION
# ============================================

class AIImageGenerator:
    """
    Generates custom illustrations using AI
    
    Supports:
    - Google Gemini (free tier available)
    - Can be extended for DALL-E, Stable Diffusion
    """
    
    # Whiteboard-style prompt template
    STYLE_PROMPT = """
    Create a simple, clean whiteboard-style illustration for: {topic}
    
    Style requirements:
    - Black line art on white/transparent background
    - Simple, hand-drawn sketch look
    - Minimalist design
    - Educational/professional appearance
    - No colors, just black outlines
    - Like a whiteboard marker drawing
    """
    
    @classmethod
    def generate_image(cls, topic: str, save_path: str) -> bool:
        """Generate whiteboard-style image using Gemini"""
        
        api_key = CONFIG.get("GEMINI_API_KEY")
        if not api_key:
            print("No Gemini API key configured")
            return False
        
        try:
            # Gemini API for image generation
            url = f"https://generativelanguage.googleapis.com/v1/models/gemini-pro-vision:generateContent"
            
            prompt = cls.STYLE_PROMPT.format(topic=topic)
            
            # Note: Gemini doesn't directly generate images
            # You would need to use Imagen API or alternative
            # This is a placeholder for the integration
            
            print(f"AI Image generation for '{topic}' - implement with your preferred API")
            return False
            
        except Exception as e:
            print(f"Error generating AI image: {e}")
            return False


# ============================================
# SCENE CONTENT BUILDER
# ============================================

class WhiteboardContentBuilder:
    """
    Builds professional whiteboard scenes with:
    - Text elements
    - Icons/SVGs
    - AI-generated images
    - Diagrams
    - Charts
    """
    
    def __init__(self):
        self.scenes = []
        self.assets_cache = {}
    
    def add_title_with_icon(self, title: str, icon_topic: str = None):
        """Add title with relevant icon"""
        
        icon_path = None
        if icon_topic:
            icon_id = IconLibrary.get_icon_for_topic(icon_topic)
            if icon_id:
                icon_path = f"{CONFIG['ICONS_DIR']}/{icon_topic.replace(' ', '_')}.svg"
                IconLibrary.download_svg(icon_id, icon_path)
        
        self.scenes.append({
            "type": "title_with_icon",
            "text": title,
            "icon_path": icon_path,
            "animation": "draw"  # Hand draws icon then writes text
        })
        
        return self
    
    def add_concept_with_illustration(self, concept: str, description: str):
        """Add concept with AI-generated illustration"""
        
        # Try to generate or fetch illustration
        image_path = f"{CONFIG['IMAGES_DIR']}/{concept.replace(' ', '_')}.png"
        
        # First try icon
        icon_id = IconLibrary.get_icon_for_topic(concept)
        if icon_id:
            svg_path = f"{CONFIG['ICONS_DIR']}/{concept.replace(' ', '_')}.svg"
            IconLibrary.download_svg(icon_id, svg_path)
            image_path = svg_path
        
        self.scenes.append({
            "type": "concept_illustration",
            "concept": concept,
            "description": description,
            "image_path": image_path,
            "animation": "draw_then_explain"
        })
        
        return self
    
    def add_comparison_diagram(self, title: str, items: List[Dict]):
        """Add comparison/versus diagram"""
        
        self.scenes.append({
            "type": "comparison",
            "title": title,
            "items": items,  # [{"name": "A", "points": [...]}, {"name": "B", "points": [...]}]
            "animation": "side_by_side"
        })
        
        return self
    
    def add_process_flow(self, title: str, steps: List[str]):
        """Add process flow diagram with arrows"""
        
        self.scenes.append({
            "type": "process_flow",
            "title": title,
            "steps": steps,
            "animation": "step_by_step"
        })
        
        return self
    
    def add_bullet_points_with_icons(self, header: str, points: List[Dict]):
        """Add bullet points where each has an icon"""
        
        # Fetch icons for each point
        for point in points:
            if "icon_topic" in point:
                icon_id = IconLibrary.get_icon_for_topic(point["icon_topic"])
                if icon_id:
                    svg_path = f"{CONFIG['ICONS_DIR']}/{point['icon_topic'].replace(' ', '_')}.svg"
                    IconLibrary.download_svg(icon_id, svg_path)
                    point["icon_path"] = svg_path
        
        self.scenes.append({
            "type": "bullet_with_icons",
            "header": header,
            "points": points,
            "animation": "sequential"
        })
        
        return self
    
    def add_image_scene(self, image_path: str, caption: str = None):
        """Add full image scene"""
        
        self.scenes.append({
            "type": "image_full",
            "image_path": image_path,
            "caption": caption,
            "animation": "draw_image"
        })
        
        return self
    
    def build(self) -> List[Dict]:
        """Return all scenes"""
        return self.scenes


# ============================================
# EXAMPLE USAGE
# ============================================

def create_ai_education_video():
    """Example: Create a professional AI education video"""
    
    builder = WhiteboardContentBuilder()
    
    # Scene 1: Title with AI brain icon
    builder.add_title_with_icon(
        title="What is Artificial Intelligence?",
        icon_topic="ai"
    )
    
    # Scene 2: Definition with robot illustration
    builder.add_concept_with_illustration(
        concept="robot",
        description="AI = Machine that can think and learn like humans"
    )
    
    # Scene 3: Types of AI (comparison)
    builder.add_comparison_diagram(
        title="Types of AI",
        items=[
            {"name": "Narrow AI", "points": ["Specific tasks", "ChatGPT, Siri"]},
            {"name": "General AI", "points": ["Human-like thinking", "Future goal"]}
        ]
    )
    
    # Scene 4: Examples with icons
    builder.add_bullet_points_with_icons(
        header="Real-World Examples:",
        points=[
            {"text": "ChatGPT - Conversational AI", "icon_topic": "robot"},
            {"text": "Self-Driving Cars", "icon_topic": "car"},
            {"text": "Voice Assistants", "icon_topic": "phone"},
            {"text": "Medical Diagnosis", "icon_topic": "biology"},
        ]
    )
    
    # Scene 5: How AI Works (process flow)
    builder.add_process_flow(
        title="How AI Learns:",
        steps=[
            "Collect Data",
            "Train Model",
            "Test & Improve",
            "Deploy Solution"
        ]
    )
    
    return builder.build()


# ============================================
# MANIM RENDERER (Professional Version)
# ============================================

MANIM_SCENE_TEMPLATE = '''
from manim import *
import os

class ProfessionalWhiteboard(Scene):
    """Professional whiteboard with images, icons, and hand animation"""
    
    def construct(self):
        self.camera.background_color = "{bg_color}"
        
        # Load hand image
        hand = self.load_hand()
        
        # Render all scenes
        scenes_data = {scenes_data}
        
        self.play(FadeIn(hand), run_time=0.3)
        
        for scene in scenes_data:
            self.render_scene(hand, scene)
        
        self.play(FadeOut(hand), run_time=0.5)
        self.wait(2)
    
    def load_hand(self):
        """Load hand image or create vector hand"""
        hand_paths = [
            "assets/hands/hand_with_pen.png",
            "assets/hand_with_pen.png",
            "hand.png"
        ]
        
        for path in hand_paths:
            if os.path.exists(path):
                hand = ImageMobject(path)
                hand.scale(0.4)
                hand.set_z_index(1000)
                return hand
        
        # Fallback: Create vector hand
        return self.create_vector_hand()
    
    def create_vector_hand(self):
        """Create vector hand as fallback"""
        palm = Circle(radius=0.25, fill_color="#FDBCB4", fill_opacity=1,
                     stroke_color="#D4A090", stroke_width=2)
        pen = Rectangle(width=0.05, height=0.4, fill_color="#333",
                       fill_opacity=1, stroke_width=0)
        pen.next_to(palm, UP, buff=-0.1)
        
        hand = VGroup(palm, pen)
        hand.set_z_index(1000)
        return hand
    
    def render_scene(self, hand, scene):
        """Render individual scene based on type"""
        scene_type = scene.get("type", "text")
        
        if scene_type == "title_with_icon":
            self.render_title_with_icon(hand, scene)
        elif scene_type == "concept_illustration":
            self.render_concept_illustration(hand, scene)
        elif scene_type == "comparison":
            self.render_comparison(hand, scene)
        elif scene_type == "process_flow":
            self.render_process_flow(hand, scene)
        elif scene_type == "bullet_with_icons":
            self.render_bullet_with_icons(hand, scene)
        elif scene_type == "image_full":
            self.render_image_full(hand, scene)
        else:
            self.render_text(hand, scene)
        
        self.wait(0.5)
    
    def render_title_with_icon(self, hand, scene):
        """Render title with icon beside it"""
        # Clear previous
        self.clear_scene()
        
        title_text = scene.get("text", "")
        icon_path = scene.get("icon_path")
        
        # Create title
        title = Text(title_text, font="Arial", font_size=48, 
                    color=BLACK, weight=BOLD)
        title.to_edge(UP, buff=0.8)
        
        # Load and draw icon if exists
        if icon_path and os.path.exists(icon_path):
            if icon_path.endswith('.svg'):
                icon = SVGMobject(icon_path)
            else:
                icon = ImageMobject(icon_path)
            
            icon.scale(0.8)
            icon.next_to(title, LEFT, buff=0.5)
            
            # Hand draws icon first
            self.draw_with_hand(hand, icon)
            self.wait(0.3)
        
        # Then writes title
        self.write_with_hand(hand, title)
        
        # Underline
        underline = Line(
            title.get_left() + DOWN * 0.2,
            title.get_right() + DOWN * 0.2,
            color="#333333", stroke_width=4
        )
        self.draw_with_hand(hand, underline, speed=0.4)
    
    def render_concept_illustration(self, hand, scene):
        """Render concept with large illustration"""
        self.clear_scene()
        
        concept = scene.get("concept", "")
        description = scene.get("description", "")
        image_path = scene.get("image_path")
        
        # Draw illustration on left side
        if image_path and os.path.exists(image_path):
            if image_path.endswith('.svg'):
                illustration = SVGMobject(image_path)
                illustration.set_color(BLACK)
            else:
                illustration = ImageMobject(image_path)
            
            illustration.scale(1.5)
            illustration.to_edge(LEFT, buff=1)
            
            self.draw_with_hand(hand, illustration, speed=2)
        
        # Write description on right
        desc_text = Text(description, font="Arial", font_size=36, color="#333333")
        desc_text.to_edge(RIGHT, buff=1)
        desc_text.shift(UP * 0.5)
        
        # Wrap text if too long
        if desc_text.width > 6:
            desc_text.scale(6 / desc_text.width)
        
        self.write_with_hand(hand, desc_text)
    
    def render_comparison(self, hand, scene):
        """Render side-by-side comparison"""
        self.clear_scene()
        
        title = scene.get("title", "")
        items = scene.get("items", [])
        
        # Title
        title_text = Text(title, font="Arial", font_size=42, 
                         color=BLACK, weight=BOLD)
        title_text.to_edge(UP, buff=0.6)
        self.write_with_hand(hand, title_text)
        
        # Dividing line
        divider = Line(UP * 1.5, DOWN * 2, color="#CCCCCC", stroke_width=2)
        self.draw_with_hand(hand, divider, speed=0.3)
        
        # Left side
        if len(items) > 0:
            left_group = self.create_comparison_side(items[0], LEFT * 3.5)
            for elem in left_group:
                self.draw_with_hand(hand, elem, speed=0.8)
        
        # Right side
        if len(items) > 1:
            right_group = self.create_comparison_side(items[1], RIGHT * 3.5)
            for elem in right_group:
                self.draw_with_hand(hand, elem, speed=0.8)
    
    def create_comparison_side(self, item, position):
        """Create one side of comparison"""
        elements = []
        
        # Header
        header = Text(item.get("name", ""), font="Arial", font_size=32,
                     color="#C0392B", weight=BOLD)
        header.move_to(position + UP * 1)
        elements.append(header)
        
        # Points
        points = item.get("points", [])
        for i, point in enumerate(points):
            point_text = Text(f"• {{point}}", font="Arial", font_size=24, color="#333333")
            point_text.move_to(position + DOWN * (i * 0.6))
            elements.append(point_text)
        
        return elements
    
    def render_process_flow(self, hand, scene):
        """Render process flow with arrows"""
        self.clear_scene()
        
        title = scene.get("title", "")
        steps = scene.get("steps", [])
        
        # Title
        title_text = Text(title, font="Arial", font_size=40,
                         color=BLACK, weight=BOLD)
        title_text.to_edge(UP, buff=0.6)
        self.write_with_hand(hand, title_text)
        
        # Calculate positions
        n_steps = len(steps)
        start_x = -5
        end_x = 5
        step_width = (end_x - start_x) / max(n_steps, 1)
        
        prev_box = None
        
        for i, step in enumerate(steps):
            # Step box
            box = RoundedRectangle(
                width=2, height=1.2, corner_radius=0.2,
                fill_color="#E8F4FD", fill_opacity=1,
                stroke_color="#3498DB", stroke_width=2
            )
            box.move_to(RIGHT * (start_x + step_width * i + step_width/2) + DOWN * 0.5)
            
            # Step text
            step_text = Text(step, font="Arial", font_size=20, color="#333333")
            step_text.move_to(box.get_center())
            
            # Scale text to fit
            if step_text.width > 1.8:
                step_text.scale(1.8 / step_text.width)
            
            # Draw box and text
            self.draw_with_hand(hand, box, speed=0.5)
            self.write_with_hand(hand, step_text, speed=0.6)
            
            # Arrow to next
            if prev_box is not None:
                arrow = Arrow(
                    prev_box.get_right(),
                    box.get_left(),
                    color="#666666",
                    stroke_width=3,
                    buff=0.1
                )
                self.draw_with_hand(hand, arrow, speed=0.3)
            
            prev_box = box
    
    def render_bullet_with_icons(self, hand, scene):
        """Render bullet points with icons"""
        self.clear_scene()
        
        header = scene.get("header", "")
        points = scene.get("points", [])
        
        # Header
        header_text = Text(header, font="Arial", font_size=38,
                          color="#C0392B", slant=ITALIC)
        header_text.to_edge(UP, buff=0.8).to_edge(LEFT, buff=1)
        self.write_with_hand(hand, header_text)
        
        # Underline
        underline = Line(
            header_text.get_left() + DOWN * 0.15,
            header_text.get_right() + DOWN * 0.15,
            color="#C0392B", stroke_width=2
        )
        self.draw_with_hand(hand, underline, speed=0.3)
        
        # Points
        for i, point in enumerate(points):
            y_offset = DOWN * (1.2 + i * 0.9)
            
            # Icon
            icon_path = point.get("icon_path")
            if icon_path and os.path.exists(icon_path):
                if icon_path.endswith('.svg'):
                    icon = SVGMobject(icon_path)
                    icon.set_color("#333333")
                else:
                    icon = ImageMobject(icon_path)
                
                icon.scale(0.4)
                icon.to_edge(LEFT, buff=1.2)
                icon.shift(y_offset + UP * 0.8)
                
                self.draw_with_hand(hand, icon, speed=0.5)
            
            # Text
            point_text = Text(point.get("text", ""), font="Arial", 
                            font_size=28, color="#333333")
            point_text.to_edge(LEFT, buff=2.2)
            point_text.shift(y_offset + UP * 0.8)
            
            self.write_with_hand(hand, point_text, speed=1)
    
    def render_image_full(self, hand, scene):
        """Render full-size image"""
        self.clear_scene()
        
        image_path = scene.get("image_path")
        caption = scene.get("caption")
        
        if image_path and os.path.exists(image_path):
            if image_path.endswith('.svg'):
                image = SVGMobject(image_path)
                image.set_color(BLACK)
            else:
                image = ImageMobject(image_path)
            
            image.scale_to_fit_width(10)
            image.center()
            
            if caption:
                image.shift(UP * 0.5)
            
            self.draw_with_hand(hand, image, speed=3)
        
        if caption:
            caption_text = Text(caption, font="Arial", font_size=28, color="#666666")
            caption_text.to_edge(DOWN, buff=0.8)
            self.write_with_hand(hand, caption_text, speed=1)
    
    def render_text(self, hand, scene):
        """Fallback: render simple text"""
        text = scene.get("text", "")
        text_obj = Text(text, font="Arial", font_size=36, color=BLACK)
        text_obj.center()
        self.write_with_hand(hand, text_obj)
    
    # ==========================================
    # ANIMATION HELPERS
    # ==========================================
    
    def draw_with_hand(self, hand, mobject, speed=1.0):
        """Animate hand drawing a mobject (icon, shape, etc.)"""
        hand_offset = DOWN * 0.3 + RIGHT * 0.2
        
        # Move to start
        if hasattr(mobject, 'get_start'):
            start = mobject.get_start() - hand_offset
        else:
            start = mobject.get_left() - hand_offset
        
        self.play(hand.animate.move_to(start), run_time=0.2)
        
        # Draw
        if isinstance(mobject, (SVGMobject, VMobject)):
            self.play(
                DrawBorderThenFill(mobject),
                hand.animate.move_to(mobject.get_right() - hand_offset),
                run_time=speed,
                rate_func=linear
            )
        else:
            self.play(
                FadeIn(mobject),
                hand.animate.move_to(mobject.get_right() - hand_offset),
                run_time=speed,
                rate_func=linear
            )
    
    def write_with_hand(self, hand, text_mobject, speed=1.2):
        """Animate hand writing text"""
        hand_offset = DOWN * 0.3 + RIGHT * 0.2
        
        start = text_mobject.get_left() - hand_offset
        end = text_mobject.get_right() - hand_offset
        
        self.play(hand.animate.move_to(start), run_time=0.2)
        
        self.play(
            Write(text_mobject),
            hand.animate.move_to(end),
            run_time=speed,
            rate_func=linear
        )
    
    def clear_scene(self):
        """Clear all elements except hand"""
        mobjects_to_remove = [m for m in self.mobjects if m.z_index < 1000]
        if mobjects_to_remove:
            self.play(*[FadeOut(m) for m in mobjects_to_remove], run_time=0.3)
'''


# ============================================
# MAIN RENDERER
# ============================================

def render_professional_whiteboard(scenes_data: List[Dict], output_name: str):
    """
    Render professional whiteboard video
    
    Args:
        scenes_data: Output from WhiteboardContentBuilder.build()
        output_name: Output video filename
    """
    import subprocess
    import tempfile
    
    # Generate Manim scene file
    scene_code = MANIM_SCENE_TEMPLATE.format(
        bg_color=CONFIG["BACKGROUND_COLOR"],
        scenes_data=repr(scenes_data)
    )
    
    # Save to temp file
    temp_file = tempfile.NamedTemporaryFile(mode='w', suffix='.py', delete=False)
    temp_file.write(scene_code)
    temp_file.close()
    
    # Render with Manim
    result = subprocess.run([
        "manim", "-qh",
        "--output_file", output_name,
        temp_file.name,
        "ProfessionalWhiteboard"
    ], capture_output=True, text=True)
    
    # Cleanup
    os.remove(temp_file.name)
    
    if result.returncode == 0:
        print(f"✅ Video rendered: {output_name}")
        return True
    else:
        print(f"❌ Render failed: {result.stderr}")
        return False


# ============================================
# QUICK TEST
# ============================================

if __name__ == "__main__":
    print("=" * 60)
    print("🎬 Professional Whiteboard Video System")
    print("=" * 60)
    
    # Create example video
    scenes = create_ai_education_video()
    print(f"\nGenerated {len(scenes)} scenes:")
    for i, scene in enumerate(scenes, 1):
        print(f"  {i}. {scene['type']}")
    
    print("\nTo render, run:")
    print("  render_professional_whiteboard(scenes, 'ai_education')")
