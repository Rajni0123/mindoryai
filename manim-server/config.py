import os
from dotenv import load_dotenv

load_dotenv()


def _setup_ffmpeg_path():
    """Add FFmpeg directory to PATH so pydub/manim can find it"""
    ffmpeg_path = os.getenv("FFMPEG_PATH", "ffmpeg")
    if ffmpeg_path and ffmpeg_path != "ffmpeg" and os.path.isfile(ffmpeg_path):
        ffmpeg_dir = os.path.dirname(ffmpeg_path)
        current_path = os.environ.get("PATH", "")
        if ffmpeg_dir not in current_path:
            os.environ["PATH"] = ffmpeg_dir + os.pathsep + current_path
            print(f"[CONFIG] Added FFmpeg to PATH: {ffmpeg_dir}")


_setup_ffmpeg_path()


class Config:
    # Flask
    SECRET_KEY = os.getenv("SECRET_KEY", "dev-secret-key")
    HOST = os.getenv("FLASK_HOST", "0.0.0.0")
    PORT = int(os.getenv("FLASK_PORT", 5000))
    DEBUG = os.getenv("FLASK_DEBUG", "false").lower() == "true"

    # Gemini AI
    GEMINI_API_KEY = os.getenv("GEMINI_API_KEY", "")
    GEMINI_MODEL = os.getenv("GEMINI_MODEL", "gemini-2.0-flash")

    # Edge TTS
    TTS_VOICE = os.getenv("TTS_VOICE", "hi-IN-MadhurNeural")
    TTS_RATE = os.getenv("TTS_RATE", "-10%")

    # Paths
    OUTPUT_DIR = os.getenv("OUTPUT_DIR", "./output")
    TEMP_DIR = os.getenv("TEMP_DIR", "./temp")

    # FFmpeg
    FFMPEG_PATH = os.getenv("FFMPEG_PATH", "ffmpeg")
    FFPROBE_PATH = os.getenv("FFPROBE_PATH", "ffprobe")

    # Laravel callback
    LARAVEL_API_URL = os.getenv("LARAVEL_API_URL", "http://localhost:8000/api")
    LARAVEL_CALLBACK_SECRET = os.getenv("LARAVEL_CALLBACK_SECRET", "")

    # Celery
    CELERY_BROKER_URL = os.getenv("CELERY_BROKER_URL", "redis://localhost:6379/0")
    CELERY_RESULT_BACKEND = os.getenv("CELERY_RESULT_BACKEND", "redis://localhost:6379/0")
