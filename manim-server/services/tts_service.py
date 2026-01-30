"""
TTS Service - Generate narration audio using Edge TTS (free, high quality)
"""

import asyncio
import os
import subprocess
import edge_tts
from config import Config


class TTSService:

    def __init__(self, voice: str = None, rate: str = None):
        self.voice = voice or Config.TTS_VOICE
        self.rate = rate or Config.TTS_RATE

    def generate(self, text: str, output_path: str) -> float:
        """Generate audio from text and return duration in seconds"""
        loop = asyncio.new_event_loop()
        try:
            loop.run_until_complete(self._generate_async(text, output_path))
        finally:
            loop.close()

        duration = self._get_duration(output_path)
        return duration

    async def _generate_async(self, text: str, output_path: str):
        """Async Edge TTS generation"""
        communicate = edge_tts.Communicate(text, self.voice, rate=self.rate)
        await communicate.save(output_path)

    def _get_duration(self, audio_path: str) -> float:
        """Get audio duration using ffprobe"""
        try:
            result = subprocess.run(
                [
                    Config.FFPROBE_PATH,
                    "-v", "quiet",
                    "-show_entries", "format=duration",
                    "-of", "csv=p=0",
                    audio_path,
                ],
                capture_output=True,
                text=True,
                timeout=10,
            )
            return float(result.stdout.strip())
        except Exception:
            return 10.0  # fallback
