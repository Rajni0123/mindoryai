"""
AI Image Generator - Multi-provider whiteboard illustration generator.
Supports: Google Imagen, OpenAI DALL-E 3, Stability AI, Pollinations (free).
Provider is selected from Laravel admin settings.
"""

import os
import requests
import traceback
from config import Config


class AIImageGenerator:
    """Generates whiteboard outline illustrations using configurable AI providers"""

    # Google Imagen models (fallback order)
    IMAGEN_MODELS = [
        "imagen-4.0-fast-generate-001",
        "imagen-3.0-fast-generate-001",
        "imagen-3.0-generate-001",
    ]

    # Track exhausted models to avoid repeated 429 calls
    _exhausted_models = set()

    def __init__(self, api_key: str = None, image_settings: dict = None):
        self.settings = image_settings or {}
        self.enabled = self.settings.get("enabled", True)
        self.provider = self.settings.get("provider", "gemini_imagen")
        self.provider_api_key = self.settings.get("api_key") or ""

        # Gemini/Imagen API key (fallback to main gemini key)
        self.gemini_key = api_key or Config.GEMINI_API_KEY

        # Initialize provider-specific client
        self._gemini_client = None
        if self.provider == "gemini_imagen" and self.enabled:
            key = self.provider_api_key or self.gemini_key
            if key:
                try:
                    from google import genai
                    self._gemini_client = genai.Client(api_key=key)
                except Exception as e:
                    print(f"[AI-IMG] Failed to init Gemini client: {e}")

    def generate_scene_image(self, title: str, description: str, concepts: list, output_path: str) -> str:
        """Generate a whiteboard-style educational diagram for a scene."""
        if not self.enabled:
            print("[AI-IMG] Image generation disabled in settings")
            return None

        concepts_str = ", ".join(concepts[:5]) if concepts else ""
        prompt = (
            f"Draw ONE single educational diagram about: {title}. "
            f"{description[:200]}. "
            f"Label these parts: {concepts_str}. "
            "STRICT RULES: "
            "1. PURE WHITE background - absolutely NO colored backgrounds, NO colored boxes, NO borders, NO containers. "
            "2. Draw ONLY ONE single focused diagram - NOT multiple separate diagrams or images side by side. "
            "3. Use directional ARROWS showing force, movement, or flow directions clearly. "
            "4. Bold black labels with thin arrows pointing to each labeled part. "
            "5. Colorful hand-drawn illustration style like a teacher drew on a clean whiteboard. "
            "6. Scientific/anatomical accuracy with colored parts. "
            "7. Landscape orientation. No photo-realistic, no 3D, no backgrounds, no frames."
        )
        return self._generate(prompt, output_path, title)

    def generate_concept_image(self, concept: str, scene_title: str, description: str, output_path: str) -> str:
        """Generate a UNIQUE visual for ONE specific concept."""
        if not self.enabled:
            return None

        prompt = (
            f"Draw ONE single educational diagram of '{concept}' for topic '{scene_title}'. "
            f"{description[:150]}. "
            f"Show '{concept}' as a clear, detailed illustration with labeled parts and directional arrows. "
            "STRICT RULES: "
            "1. PURE WHITE background - absolutely NO colored backgrounds, NO colored boxes, NO containers, NO borders. "
            "2. ONLY ONE single focused diagram - NOT multiple images or panels. "
            "3. Use ARROWS showing direction of force, movement, flow, or connections between parts. "
            "4. Bold black labels with thin arrows pointing to parts. "
            "5. Colorful hand-drawn style like a teacher's whiteboard sketch. "
            "6. Landscape. No photo-realistic, no 3D, no background color."
        )
        return self._generate(prompt, output_path, concept)

    def _generate(self, prompt: str, output_path: str, label: str) -> str:
        """Route to the selected provider."""
        os.makedirs(os.path.dirname(output_path), exist_ok=True)

        provider_map = {
            "gemini_imagen": self._generate_gemini_imagen,
            "openai_dalle": self._generate_openai_dalle,
            "openai_gpt_image": self._generate_openai_gpt_image,
            "stability_ai": self._generate_stability_ai,
            "pollinations": self._generate_pollinations,
        }

        generator = provider_map.get(self.provider, self._generate_gemini_imagen)
        result = generator(prompt, output_path, label)

        if not result:
            print(f"[AI-IMG] FAILED for: {label[:50]} (provider: {self.provider})")
            return result

        # Post-process: remove background for cleaner whiteboard look
        self._try_remove_background(result)
        return result

    # =========================================================================
    # GOOGLE IMAGEN (via Gemini API)
    # =========================================================================
    def _generate_gemini_imagen(self, prompt: str, output_path: str, label: str) -> str:
        """Try all Imagen models to generate an image."""
        if not self._gemini_client:
            print("[AI-IMG] Gemini client not initialized")
            return None

        for model_name in self.IMAGEN_MODELS:
            if model_name in self._exhausted_models:
                continue
            result = self._try_imagen(model_name, prompt, output_path)
            if result:
                return result
        return None

    def _try_imagen(self, model: str, prompt: str, output_path: str) -> str:
        try:
            from google.genai import types
            print(f"[AI-IMG] Trying {model}...")
            response = self._gemini_client.models.generate_images(
                model=model,
                prompt=prompt,
                config=types.GenerateImagesConfig(
                    number_of_images=1,
                    output_mime_type="image/png",
                ),
            )
            if response.generated_images:
                with open(output_path, "wb") as f:
                    f.write(response.generated_images[0].image.image_bytes)
                print(f"[AI-IMG] OK ({model}): {output_path}")
                return output_path
        except Exception as e:
            err_str = str(e)
            print(f"[AI-IMG] {model} error: {err_str[:200]}")
            if "429" in err_str or "RESOURCE_EXHAUSTED" in err_str:
                self._exhausted_models.add(model)
                print(f"[AI-IMG] Marked {model} as quota-exhausted, skipping for this session")
        return None

    # =========================================================================
    # OPENAI DALL-E 3
    # =========================================================================
    def _generate_openai_dalle(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using OpenAI DALL-E 3 API."""
        api_key = self.provider_api_key
        if not api_key:
            print("[AI-IMG] OpenAI API key not configured")
            return None

        try:
            print(f"[AI-IMG] Trying DALL-E 3 for: {label[:40]}...")
            response = requests.post(
                "https://api.openai.com/v1/images/generations",
                headers={
                    "Authorization": f"Bearer {api_key}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": "dall-e-3",
                    "prompt": prompt,
                    "n": 1,
                    "size": "1792x1024",
                    "quality": "standard",
                    "response_format": "url",
                },
                timeout=60,
            )

            if response.status_code == 200:
                data = response.json()
                image_url = data["data"][0]["url"]
                # Download the image
                img_response = requests.get(image_url, timeout=30)
                if img_response.status_code == 200:
                    with open(output_path, "wb") as f:
                        f.write(img_response.content)
                    print(f"[AI-IMG] OK (dall-e-3): {output_path}")
                    return output_path
            else:
                err = response.json().get("error", {}).get("message", response.text[:200])
                print(f"[AI-IMG] DALL-E 3 error ({response.status_code}): {err}")
        except Exception as e:
            print(f"[AI-IMG] DALL-E 3 exception: {str(e)[:200]}")
        return None

    # =========================================================================
    # OPENAI GPT-IMAGE-1 (newest model)
    # =========================================================================
    def _generate_openai_gpt_image(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using OpenAI gpt-image-1 model."""
        api_key = self.provider_api_key
        if not api_key:
            print("[AI-IMG] OpenAI API key not configured for gpt-image-1")
            return None

        try:
            print(f"[AI-IMG] Trying gpt-image-1 for: {label[:40]}...")
            response = requests.post(
                "https://api.openai.com/v1/images/generations",
                headers={
                    "Authorization": f"Bearer {api_key}",
                    "Content-Type": "application/json",
                },
                json={
                    "model": "gpt-image-1",
                    "prompt": prompt,
                    "n": 1,
                    "size": "1536x1024",
                    "quality": "low",
                },
                timeout=90,
            )

            if response.status_code == 200:
                data = response.json()
                # gpt-image-1 returns base64 by default
                image_data = data["data"][0]
                if "b64_json" in image_data:
                    import base64
                    img_bytes = base64.b64decode(image_data["b64_json"])
                    with open(output_path, "wb") as f:
                        f.write(img_bytes)
                elif "url" in image_data:
                    img_response = requests.get(image_data["url"], timeout=30)
                    if img_response.status_code == 200:
                        with open(output_path, "wb") as f:
                            f.write(img_response.content)
                    else:
                        print(f"[AI-IMG] gpt-image-1 download failed: {img_response.status_code}")
                        return None
                print(f"[AI-IMG] OK (gpt-image-1): {output_path}")
                return output_path
            else:
                err = response.json().get("error", {}).get("message", response.text[:200])
                print(f"[AI-IMG] gpt-image-1 error ({response.status_code}): {err}")
        except Exception as e:
            print(f"[AI-IMG] gpt-image-1 exception: {str(e)[:200]}")
        return None

    # =========================================================================
    # STABILITY AI (Stable Diffusion)
    # =========================================================================
    def _generate_stability_ai(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using Stability AI API."""
        api_key = self.provider_api_key
        if not api_key:
            print("[AI-IMG] Stability AI API key not configured")
            return None

        try:
            print(f"[AI-IMG] Trying Stability AI for: {label[:40]}...")
            response = requests.post(
                "https://api.stability.ai/v2beta/stable-image/generate/core",
                headers={
                    "Authorization": f"Bearer {api_key}",
                    "Accept": "image/*",
                },
                files={"none": ""},
                data={
                    "prompt": prompt,
                    "output_format": "png",
                    "aspect_ratio": "16:9",
                },
                timeout=60,
            )

            if response.status_code == 200:
                with open(output_path, "wb") as f:
                    f.write(response.content)
                print(f"[AI-IMG] OK (stability-ai): {output_path}")
                return output_path
            else:
                try:
                    err = response.json().get("message", response.text[:200])
                except Exception:
                    err = response.text[:200]
                print(f"[AI-IMG] Stability AI error ({response.status_code}): {err}")
        except Exception as e:
            print(f"[AI-IMG] Stability AI exception: {str(e)[:200]}")
        return None

    # =========================================================================
    # POLLINATIONS.AI (FREE - no API key needed)
    # =========================================================================
    def _generate_pollinations(self, prompt: str, output_path: str, label: str) -> str:
        """Generate image using Pollinations.ai (free, no API key)."""
        try:
            import urllib.parse
            import time
            print(f"[AI-IMG] Trying Pollinations (free) for: {label[:40]}...")
            encoded_prompt = urllib.parse.quote(prompt)
            seed = int(time.time()) % 100000
            url = f"https://image.pollinations.ai/prompt/{encoded_prompt}?width=1792&height=1024&nologo=true&seed={seed}"

            response = requests.get(url, timeout=90)
            if response.status_code == 200 and len(response.content) > 1000:
                with open(output_path, "wb") as f:
                    f.write(response.content)
                print(f"[AI-IMG] OK (pollinations): {output_path}")
                return output_path
            else:
                print(f"[AI-IMG] Pollinations error: status={response.status_code}, size={len(response.content)}")
        except Exception as e:
            print(f"[AI-IMG] Pollinations exception: {str(e)[:200]}")
        return None

    # =========================================================================
    # POST-PROCESSING: Background Removal
    # =========================================================================
    def _try_remove_background(self, image_path: str):
        """Remove background from generated image for cleaner whiteboard compositing.
        Uses rembg if available, otherwise silently skips."""
        try:
            from rembg import remove
            from PIL import Image

            img = Image.open(image_path)
            result = remove(img)
            # Paste onto white background (whiteboard)
            white_bg = Image.new("RGBA", result.size, (255, 255, 255, 255))
            white_bg.paste(result, mask=result.split()[3] if result.mode == "RGBA" else None)
            white_bg.convert("RGB").save(image_path, "PNG")
            print(f"[AI-IMG] Background removed: {os.path.basename(image_path)}")
        except ImportError:
            pass  # rembg not installed, skip silently
        except Exception as e:
            print(f"[AI-IMG] Background removal skipped: {str(e)[:100]}")
