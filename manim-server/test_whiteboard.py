from manim import *

STORY_LINES = [
    "Photosynthesis is how plants make food",
    "Plants use sunlight as energy",
    "They take carbon dioxide from air",
    "They absorb water from soil",
    "Chlorophyll makes plants green",
    "Oxygen is released as byproduct"
]

class Whiteboard(Scene):
    def construct(self):
        # White background - clean whiteboard
        self.camera.background_color = WHITE

        for line in STORY_LINES:
            # Create text
            text = Text(
                line,
                font="Arial",
                font_size=42,
                color=BLACK,
                weight=NORMAL
            )
            text.move_to(ORIGIN)

            # Animate: write text, wait, then clear
            self.play(Write(text), run_time=2.0)
            self.wait(0.4)
            self.clear()
