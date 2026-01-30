"""
Mindory Manim Whiteboard Video Server
Flask API that generates animated whiteboard videos using Manim + Edge TTS + FFmpeg
"""

import os
import sys
import uuid
import json
import threading
import traceback
from datetime import datetime

# Fix Windows encoding for non-ASCII (Hindi, Bengali, etc.)
import builtins
_original_print = builtins.print
def _safe_print(*args, **kwargs):
    try:
        _original_print(*args, **kwargs)
    except (UnicodeEncodeError, UnicodeDecodeError):
        # Fallback: replace non-ASCII chars with '?'
        safe_args = []
        for a in args:
            if isinstance(a, str):
                safe_args.append(a.encode("ascii", errors="replace").decode("ascii"))
            else:
                safe_args.append(a)
        try:
            _original_print(*safe_args, **kwargs)
        except Exception:
            pass  # Give up printing, don't crash
builtins.print = _safe_print

from flask import Flask, request, jsonify, send_file
from flask_cors import CORS

from config import Config
from services.storyboard_generator import StoryboardGenerator
from services.manim_renderer import ManimRenderer
from services.tts_service import TTSService
from services.video_stitcher import VideoStitcher

# Professional mode - AI-generated images (no static assets needed)
try:
    from services.professional_manim_renderer import ProfessionalManimRenderer
    PROFESSIONAL_MODE_AVAILABLE = True
    print("[INFO] Professional mode available (AI-generated images)")
except ImportError as e:
    PROFESSIONAL_MODE_AVAILABLE = False
    print(f"[WARN] Professional mode not available: {e}")

app = Flask(__name__)
app.config.from_object(Config)
CORS(app)

# In-memory job tracking (use Redis/DB in production)
jobs: dict = {}

# Ensure output directories exist
os.makedirs(Config.OUTPUT_DIR, exist_ok=True)
os.makedirs(Config.TEMP_DIR, exist_ok=True)


def process_video(job_id: str, topic: str, voice: str, rate: str, gemini_api_key: str = None, gemini_model: str = None, mode: str = "simple", language: str = "en", image_settings: dict = None, storyboard_settings: dict = None):
    """
    Background video generation pipeline

    Args:
        mode: "simple" (text-only) or "professional" (icons + hand + diagrams)
    """
    job = jobs[job_id]
    job["mode"] = mode
    job_dir = os.path.join(Config.TEMP_DIR, job_id)
    os.makedirs(job_dir, exist_ok=True)
    scenes_dir = os.path.join(job_dir, "scenes")
    audio_dir = os.path.join(job_dir, "audio")
    os.makedirs(scenes_dir, exist_ok=True)
    os.makedirs(audio_dir, exist_ok=True)

    try:
        # Step 1: Generate storyboard using selected AI provider
        job["status"] = "generating_storyboard"
        job["progress"] = 10
        sb_settings = storyboard_settings or {}
        storyboard_gen = StoryboardGenerator(
            api_key=gemini_api_key,
            model_name=gemini_model,
            storyboard_settings=sb_settings,
        )
        storyboard = storyboard_gen.generate(topic, language=language)
        scenes = storyboard["scenes"]
        job["total_scenes"] = len(scenes)
        job["storyboard"] = storyboard
        job["progress"] = 20

        # Step 2: Generate audio for each scene using Edge TTS
        job["status"] = "generating_audio"
        tts = TTSService(voice=voice, rate=rate)
        audio_paths = {}
        for i, scene in enumerate(scenes):
            audio_file = os.path.join(audio_dir, f"scene_{scene['scene_number']}.mp3")
            duration = tts.generate(scene["narration"], audio_file)
            audio_paths[scene["scene_number"]] = {
                "path": audio_file,
                "duration": duration,
            }
            job["processed_scenes"] = i + 1
            job["progress"] = 20 + int((i + 1) / len(scenes) * 25)

        # Step 3: Render Manim whiteboard animations for each scene
        job["status"] = "generating_assets"

        # Choose renderer based on mode
        if mode == "professional" and PROFESSIONAL_MODE_AVAILABLE:
            print(f"[PROFESSIONAL MODE] Using AI-powered renderer")
            renderer = ProfessionalManimRenderer(
                output_dir=scenes_dir,
                gemini_api_key=gemini_api_key,
                gemini_model=gemini_model,
                language=language,
                image_settings=image_settings,
            )
        else:
            print(f"[SIMPLE MODE] Using ManimRenderer")
            renderer = ManimRenderer(output_dir=scenes_dir, language=language)

        video_clips = {}
        for i, scene in enumerate(scenes):
            audio_dur = audio_paths[scene["scene_number"]]["duration"]
            clip_path = renderer.render_scene(
                scene_number=scene["scene_number"],
                visual_description=scene["visual_description"],
                narration=scene["narration"],
                duration=audio_dur,
                key_concepts=scene.get("key_concepts", []),
                elements=scene.get("elements", {}),
            )
            video_clips[scene["scene_number"]] = clip_path
            job["processed_scenes"] = i + 1
            job["progress"] = 45 + int((i + 1) / len(scenes) * 30)

        # Step 4: Stitch everything together with FFmpeg
        job["status"] = "stitching_video"
        job["progress"] = 80
        stitcher = VideoStitcher()
        final_path = os.path.join(Config.OUTPUT_DIR, f"{job_id}.mp4")
        total_duration = stitcher.stitch(
            scenes=scenes,
            video_clips=video_clips,
            audio_paths=audio_paths,
            output_path=final_path,
        )

        # Step 5: Generate thumbnail
        thumbnail_path = os.path.join(Config.OUTPUT_DIR, f"{job_id}_thumb.jpg")
        stitcher.generate_thumbnail(final_path, thumbnail_path)

        job["status"] = "completed"
        job["progress"] = 100
        job["final_video_path"] = final_path
        job["thumbnail_path"] = thumbnail_path
        job["duration_seconds"] = total_duration
        job["completed_at"] = datetime.utcnow().isoformat()

        # Notify Laravel backend
        _notify_laravel(job_id, job)

    except Exception as e:
        job["status"] = "failed"
        job["error_message"] = str(e)
        job["progress"] = 0
        print(f"[ERROR] Job {job_id} failed: {e}")
        traceback.print_exc()
        _notify_laravel(job_id, job)

    finally:
        # Cleanup temp files (keep output)
        import shutil
        if os.path.exists(job_dir):
            shutil.rmtree(job_dir, ignore_errors=True)


def _notify_laravel(job_id: str, job: dict):
    """Notify Laravel backend about job completion/failure"""
    try:
        import urllib.request
        callback_url = f"{Config.LARAVEL_API_URL}/whiteboard-video/callback"
        payload = json.dumps({
            "job_id": job_id,
            "status": job["status"],
            "final_video_path": job.get("final_video_path"),
            "thumbnail_path": job.get("thumbnail_path"),
            "duration_seconds": job.get("duration_seconds"),
            "error_message": job.get("error_message"),
            "secret": Config.LARAVEL_CALLBACK_SECRET,
        }).encode("utf-8")
        req = urllib.request.Request(
            callback_url,
            data=payload,
            headers={"Content-Type": "application/json"},
            method="POST",
        )
        urllib.request.urlopen(req, timeout=10)
    except Exception as e:
        print(f"[WARN] Failed to notify Laravel: {e}")


# ─── API ROUTES ───────────────────────────────────────────────


@app.route("/health", methods=["GET"])
def health():
    """Health check endpoint"""
    return jsonify({
        "status": "ok",
        "service": "manim-whiteboard-server",
        "version": "1.0.0",
    })


@app.route("/generate", methods=["POST"])
def generate_video():
    """
    Start whiteboard video generation

    Accepts mode parameter:
    - "simple": Text-only animations (default, fast)
    - "professional": Icons + Hand + Diagrams (slower, better quality)
    """
    data = request.get_json()

    if not data or not data.get("topic"):
        return jsonify({"error": "topic is required"}), 400

    topic = data["topic"]
    title = data.get("title", topic)
    voice = data.get("voice", Config.TTS_VOICE)
    rate = data.get("rate", Config.TTS_RATE)
    mode = data.get("mode", "simple")  # "simple" or "professional"
    language = data.get("language", "en")  # Content language
    laravel_job_id = data.get("laravel_job_id")  # To sync with Laravel
    gemini_api_key = data.get("gemini_api_key")  # From Laravel admin panel
    gemini_model = data.get("gemini_model")  # From Laravel admin panel

    # Storyboard AI settings from Laravel admin panel
    storyboard_settings = {
        "provider": data.get("storyboard_provider", "gemini"),
        "openai_model": data.get("storyboard_openai_model", "gpt-4o"),
        "openai_key": data.get("storyboard_openai_key"),
        "deepseek_model": data.get("storyboard_deepseek_model", "deepseek-chat"),
        "deepseek_key": data.get("storyboard_deepseek_key"),
    }

    # Image generation settings from Laravel admin panel
    image_settings = {
        "enabled": data.get("image_generation_enabled", False),
        "provider": data.get("image_provider", "gemini_imagen"),
        "api_key": data.get("image_api_key"),
    }

    # Validate mode
    if mode not in ["simple", "professional"]:
        return jsonify({"error": "mode must be 'simple' or 'professional'"}), 400

    # Check if professional mode is available
    if mode == "professional" and not PROFESSIONAL_MODE_AVAILABLE:
        return jsonify({"error": "Professional mode not available"}), 400

    job_id = laravel_job_id or str(uuid.uuid4())

    jobs[job_id] = {
        "job_id": job_id,
        "title": title,
        "topic": topic,
        "status": "pending",
        "progress": 0,
        "total_scenes": 0,
        "processed_scenes": 0,
        "error_message": None,
        "final_video_path": None,
        "thumbnail_path": None,
        "duration_seconds": None,
        "storyboard": None,
        "created_at": datetime.utcnow().isoformat(),
        "completed_at": None,
    }

    # Start processing in background thread
    thread = threading.Thread(
        target=process_video,
        args=(job_id, topic, voice, rate, gemini_api_key, gemini_model, mode, language, image_settings, storyboard_settings),
        daemon=True,
    )
    thread.start()

    return jsonify({
        "success": True,
        "job_id": job_id,
        "mode": mode,
        "message": f"Video generation started ({mode} mode)",
    }), 202


@app.route("/status/<job_id>", methods=["GET"])
def get_status(job_id: str):
    """Get video generation status"""
    job = jobs.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    return jsonify({
        "success": True,
        "data": {
            "job_id": job["job_id"],
            "title": job["title"],
            "status": job["status"],
            "progress_percentage": job["progress"],
            "total_scenes": job["total_scenes"],
            "processed_scenes": job["processed_scenes"],
            "duration_seconds": job["duration_seconds"],
            "error_message": job["error_message"],
            "created_at": job["created_at"],
            "completed_at": job["completed_at"],
        },
    })


@app.route("/video/<job_id>", methods=["GET"])
def get_video(job_id: str):
    """Download/stream the final video"""
    job = jobs.get(job_id)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    if job["status"] != "completed" or not job.get("final_video_path"):
        return jsonify({"error": "Video not ready"}), 404

    path = job["final_video_path"]
    if not os.path.exists(path):
        return jsonify({"error": "Video file not found"}), 404

    return send_file(path, mimetype="video/mp4", as_attachment=False)


@app.route("/thumbnail/<job_id>", methods=["GET"])
def get_thumbnail(job_id: str):
    """Get video thumbnail"""
    job = jobs.get(job_id)
    if not job or not job.get("thumbnail_path"):
        return jsonify({"error": "Thumbnail not found"}), 404

    path = job["thumbnail_path"]
    if not os.path.exists(path):
        return jsonify({"error": "Thumbnail file not found"}), 404

    return send_file(path, mimetype="image/jpeg")


@app.route("/jobs", methods=["GET"])
def list_jobs():
    """List all jobs"""
    job_list = []
    for j in jobs.values():
        job_list.append({
            "job_id": j["job_id"],
            "title": j["title"],
            "status": j["status"],
            "progress_percentage": j["progress"],
            "duration_seconds": j["duration_seconds"],
            "created_at": j["created_at"],
        })
    return jsonify({"success": True, "data": job_list})


@app.route("/delete/<job_id>", methods=["DELETE"])
def delete_job(job_id: str):
    """Delete a job and its files"""
    job = jobs.pop(job_id, None)
    if not job:
        return jsonify({"error": "Job not found"}), 404

    # Remove output files
    for key in ["final_video_path", "thumbnail_path"]:
        path = job.get(key)
        if path and os.path.exists(path):
            os.remove(path)

    return jsonify({"success": True, "message": "Deleted"})


@app.route("/voices", methods=["GET"])
def list_voices():
    """List available Edge TTS voices"""
    return jsonify({
        "success": True,
        "voices": [
            {"id": "hi-IN-MadhurNeural", "name": "Madhur (Hindi Male)", "lang": "Hindi"},
            {"id": "hi-IN-SwaraNeural", "name": "Swara (Hindi Female)", "lang": "Hindi"},
            {"id": "en-IN-NeerjaNeural", "name": "Neerja (English-IN Female)", "lang": "English"},
            {"id": "en-IN-PrabhatNeural", "name": "Prabhat (English-IN Male)", "lang": "English"},
            {"id": "en-US-JennyNeural", "name": "Jenny (US Female)", "lang": "English"},
            {"id": "en-US-GuyNeural", "name": "Guy (US Male)", "lang": "English"},
            {"id": "en-GB-SoniaNeural", "name": "Sonia (UK Female)", "lang": "English"},
        ],
    })


if __name__ == "__main__":
    print(f"Starting Manim Whiteboard Server on {Config.HOST}:{Config.PORT}")
    app.run(host=Config.HOST, port=Config.PORT, debug=Config.DEBUG)
