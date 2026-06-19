import 'dart:io';

import 'package:camera/camera.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_markdown/flutter_markdown.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import 'package:permission_handler/permission_handler.dart';
import '../../core/theme/app_theme.dart';
import '../../core/theme/dashboard_theme.dart';
import '../../providers/providers.dart';

class ScanSolveScreen extends ConsumerStatefulWidget {
  const ScanSolveScreen({super.key});

  @override
  ConsumerState<ScanSolveScreen> createState() => _ScanSolveScreenState();
}

class _ScanSolveScreenState extends ConsumerState<ScanSolveScreen>
    with TickerProviderStateMixin, WidgetsBindingObserver {
  late TabController _tabController;
  late AnimationController _scanAnim;
  CameraController? _camera;
  bool _cameraReady = false;
  bool _cameraError = false;
  bool _autoScanScheduled = false;
  bool _initializing = false;
  bool _pendingCapture = false;
  bool _capturing = false;

  File? _image;
  bool _solving = false;
  bool _saving = false;
  bool _showSteps = true;
  String? _solution;
  String? _error;
  String _statusText = 'Point camera at your question';
  final _picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(_onTabChanged);
    _scanAnim = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1800),
    )..repeat(reverse: true);

    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (ref.read(navIndexProvider) == 2) _initCamera();
    });
  }

  void _onTabChanged() {
    if (_tabController.index == 0 && ref.read(navIndexProvider) == 2) {
      if (_solving || _image != null || _solution != null) return;
      if (_cameraReady) {
        _scheduleAutoScan();
      } else if (!_cameraError) {
        _initCamera();
      }
    }
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (_capturing || _solving) return;
    final c = _camera;
    if (c == null || !c.value.isInitialized) return;
    if (state == AppLifecycleState.inactive || state == AppLifecycleState.paused) {
      c.dispose();
      _camera = null;
      _cameraReady = false;
    } else if (state == AppLifecycleState.resumed &&
        _tabController.index == 0 &&
        ref.read(navIndexProvider) == 2) {
      _initCamera();
    }
  }

  Future<void> _initCamera() async {
    if (_initializing || _solving) return;
    if (ref.read(navIndexProvider) != 2) return;
    if (_cameraReady && _camera?.value.isInitialized == true) {
      _flushPendingCapture();
      return;
    }

    final status = await Permission.camera.request();
    if (!status.isGranted) {
      setState(() {
        _cameraError = true;
        _statusText = 'Allow camera permission to scan';
      });
      return;
    }

    try {
      _initializing = true;
      final cameras = await availableCameras();
      if (cameras.isEmpty) {
        setState(() {
          _cameraError = true;
          _statusText = 'No camera found on device';
        });
        return;
      }
      final back = cameras.firstWhere(
        (c) => c.lensDirection == CameraLensDirection.back,
        orElse: () => cameras.first,
      );
      await _camera?.dispose();
      final controller = CameraController(
        back,
        ResolutionPreset.medium,
        enableAudio: false,
        imageFormatGroup: ImageFormatGroup.jpeg,
      );
      await controller.initialize();
      if (!mounted) {
        await controller.dispose();
        return;
      }
      setState(() {
        _camera = controller;
        _cameraReady = true;
        _cameraError = false;
        _statusText = 'Auto-scanning...';
      });
      _scheduleAutoScan();
      _flushPendingCapture();
    } catch (_) {
      if (mounted) {
        setState(() {
          _cameraError = true;
          _statusText = 'Could not start camera';
          _pendingCapture = false;
        });
      }
    } finally {
      _initializing = false;
    }
  }

  void _flushPendingCapture() {
    if (!_pendingCapture || _solving) return;
    if (_camera?.value.isInitialized != true) return;
    _pendingCapture = false;
    _captureFromInAppCamera();
  }

  Future<void> _requestCapture() async {
    if (_solving) return;
    HapticFeedback.mediumImpact();

    if (_tabController.index != 0) {
      _tabController.animateTo(0);
      await Future.delayed(const Duration(milliseconds: 250));
    }

    if (_camera?.value.isInitialized == true) {
      await _captureFromInAppCamera();
      return;
    }

    _pendingCapture = true;
    setState(() => _statusText = 'Opening in-app camera...');
    await _initCamera();
  }

  void _scheduleAutoScan() {
    if (_autoScanScheduled || _solving || _solution != null || _image != null) return;
    if (ref.read(navIndexProvider) != 2 || _tabController.index != 0) return;
    if (!_cameraReady) return;

    _autoScanScheduled = true;
    Future.delayed(const Duration(milliseconds: 1100), () {
      if (!mounted) return;
      _autoScanScheduled = false;
      if (ref.read(navIndexProvider) != 2 || _tabController.index != 0) return;
      if (_solving || _camera?.value.isInitialized != true) return;
      _captureFromInAppCamera(auto: true);
    });
  }

  Future<void> _pauseCamera() async {
    _autoScanScheduled = false;
    _pendingCapture = false;
    if (_camera != null) {
      await _camera!.dispose();
      _camera = null;
    }
    if (mounted) setState(() => _cameraReady = false);
  }

  Future<void> _stopScannerAfterCapture() async {
    _scanAnim.stop();
    await _pauseCamera();
  }

  Future<void> _startScanner() async {
    if (_solving) return;
    _scanAnim.repeat(reverse: true);
    await _initCamera();
  }

  Future<void> _captureFromInAppCamera({bool auto = false}) async {
    if (_solving) return;
    if (_camera?.value.isInitialized != true) {
      if (!auto) {
        _pendingCapture = true;
        setState(() => _statusText = 'Waiting for camera...');
        await _initCamera();
      }
      return;
    }

    _capturing = true;
    late final String path;
    try {
      final shot = await _camera!.takePicture();
      path = shot.path;
    } catch (_) {
      if (!auto && mounted) {
        setState(() => _statusText = 'Capture failed — tap scan again');
      }
      return;
    } finally {
      _capturing = false;
    }

    await _stopScannerAfterCapture();

    if (!mounted) return;
    setState(() {
      _image = File(path);
      _solution = null;
      _error = null;
      _showSteps = true;
      _solving = true;
      _statusText = 'OCR scanning & solving...';
    });
    await _solveFast(path);
  }

  Future<void> _pickImage(ImageSource source) async {
    if (_solving) return;
    final picked = await _picker.pickImage(
      source: source,
      imageQuality: 72,
      maxWidth: 1280,
      maxHeight: 1280,
    );
    if (picked == null) return;
    await _stopScannerAfterCapture();
    setState(() {
      _image = File(picked.path);
      _solution = null;
      _error = null;
      _solving = true;
      _statusText = 'OCR scanning & solving...';
    });
    await _solveFast(picked.path);
  }

  Future<void> _solveFast(String path) async {
    if (!_solving && mounted) setState(() => _solving = true);
    final started = DateTime.now();
    try {
      final api = ref.read(apiServiceProvider);
      final reply = await api.solveImageFast(path);
      if (!mounted) return;
      if (reply.trim().isEmpty) {
        setState(() {
          _error = 'No solution returned. Point camera at a clearer question.';
          _statusText = 'Scan failed — try again';
        });
      } else {
        setState(() {
          _solution = reply;
          _statusText = 'Solution ready';
        });
        final ms = DateTime.now().difference(started).inMilliseconds;
        if (ms < 8000) HapticFeedback.lightImpact();
      }
    } catch (e) {
      if (!mounted) return;
      final msg = e.toString().toLowerCase();
      setState(() {
        _error = msg.contains('limit')
            ? 'Daily scan limit reached. Upgrade your plan.'
            : msg.contains('unavailable') || msg.contains('busy')
                ? 'AI scan is temporarily unavailable. Try again in a minute.'
                : 'Could not solve. Retake with better lighting.';
        _statusText = 'Scan failed — try again';
      });
    } finally {
      if (mounted) setState(() => _solving = false);
    }
  }

  void _resetScan() {
    setState(() {
      _solution = null;
      _image = null;
      _error = null;
      _solving = false;
      _statusText = 'Point camera at your question';
    });
    _startScanner();
  }

  Future<void> _saveToRevision() async {
    if (_solution == null || _saving) return;
    setState(() => _saving = true);
    final ok = await ref.read(savedNotesProvider.notifier).save(
          content: _solution!,
          subject: 'Scan & Solve',
          source: 'Scan & Solve',
        );
    if (!mounted) return;
    setState(() => _saving = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          ok ? 'Saved to revision notes' : 'Could not save note',
        ),
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final c = context.dash;

    ref.listen<int>(navIndexProvider, (prev, next) {
      if (next != 2) {
        _scanAnim.stop();
        _pauseCamera();
      } else if (_tabController.index == 0 &&
          !_solving &&
          _solution == null &&
          _error == null &&
          _image == null) {
        _startScanner();
      }
    });

    ref.listen<int>(scanCaptureTriggerProvider, (prev, next) {
      if (next > (prev ?? 0)) _requestCapture();
    });

    return Scaffold(
      backgroundColor: c.background,
      appBar: AppBar(
        title: const Text('Scan & Solve'),
        actions: [
          if (_solution != null || _error != null)
            IconButton(
              icon: const Icon(Icons.refresh_rounded),
              onPressed: _resetScan,
            ),
        ],
        bottom: TabBar(
          controller: _tabController,
          labelColor: AppColors.primary,
          unselectedLabelColor: c.textMuted,
          indicatorColor: AppColors.primary,
          tabs: const [
            Tab(text: 'Camera'),
            Tab(text: 'Gallery'),
          ],
        ),
      ),
      body: Column(
        children: [
          Expanded(
            flex: _solution != null || _error != null ? 2 : 3,
            child: TabBarView(
              controller: _tabController,
              children: [
                _buildCameraView(c),
                _buildGalleryView(c),
              ],
            ),
          ),
          if (_solving) _buildScanningBar(),
          if (_error != null) _buildErrorPanel(c),
          if (_solution != null) Expanded(flex: 3, child: _buildSolution(c)),
        ],
      ),
    );
  }

  Widget _buildCameraView(DashboardColors c) {
    return Stack(
      fit: StackFit.expand,
      children: [
        Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 56),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(20),
            child: ColoredBox(
              color: const Color(0xFF0D0D12),
              child: Stack(
                fit: StackFit.expand,
                children: [
                  if (_image != null)
                    Stack(
                      fit: StackFit.expand,
                      children: [
                        Image.file(_image!, fit: BoxFit.cover),
                        if (_solving)
                          Container(
                            color: Colors.black54,
                            child: const Center(
                              child: Column(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  SizedBox(
                                    width: 36,
                                    height: 36,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2.5,
                                      color: AppColors.primary,
                                    ),
                                  ),
                                  SizedBox(height: 12),
                                  Text(
                                    'Solving...',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                      ],
                    )
                  else if (_cameraReady && _camera != null && !_solving)
                    CameraPreview(_camera!)
                  else if (_cameraError)
                    _cameraMessage(Icons.no_photography_rounded, _statusText)
                  else if (_solving)
                    _cameraMessage(Icons.auto_fix_high_rounded, _statusText)
                  else
                    _cameraMessage(Icons.camera_alt_rounded, 'Starting camera...'),
                  ..._cornerBrackets(),
                  if (_cameraReady && !_solving && _solution == null && _image == null)
                    _ScanLineOverlay(animation: _scanAnim),
                  if (!_cameraReady && !_cameraError && !_solving && _image == null)
                    const Center(
                      child: SizedBox(
                        width: 28,
                        height: 28,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: AppColors.primary,
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ),
        Positioned(
          bottom: 20,
          left: 24,
          right: 24,
          child: Text(
            _statusText,
            textAlign: TextAlign.center,
            style: TextStyle(fontSize: 12, color: c.textMuted),
          ),
        ),
      ],
    );
  }

  Widget _buildGalleryView(DashboardColors c) {
    return GestureDetector(
      onTap: _solving ? null : () => _pickImage(ImageSource.gallery),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(16, 12, 16, 56),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(20),
          child: ColoredBox(
            color: const Color(0xFF0D0D12),
            child: Stack(
              fit: StackFit.expand,
              children: [
                if (_image != null)
                  Image.file(_image!, fit: BoxFit.cover)
                else
                  _cameraMessage(
                    Icons.photo_library_rounded,
                    'Tap to pick from gallery',
                  ),
                ..._cornerBrackets(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _cameraMessage(IconData icon, String text) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 40, color: Colors.white24),
          const SizedBox(height: 10),
          Text(
            text,
            style: const TextStyle(color: Colors.white54, fontSize: 13),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildScanningBar() {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 14),
      color: AppColors.primary.withValues(alpha: 0.12),
      child: const Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          SizedBox(
            width: 18,
            height: 18,
            child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
          ),
          SizedBox(width: 12),
          Text(
            'OCR scanning & solving...',
            style: TextStyle(fontWeight: FontWeight.w600, color: AppColors.primary),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorPanel(DashboardColors c) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 8, 16, 0),
      child: Material(
        color: AppColors.error.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        child: ListTile(
          leading: const Icon(Icons.error_outline, color: AppColors.error),
          title: Text(_error!, style: TextStyle(fontSize: 13, color: c.textPrimary)),
          trailing: TextButton(onPressed: _requestCapture, child: const Text('Retry')),
        ),
      ),
    );
  }

  Widget _buildSolution(DashboardColors c) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 8, 16, 16),
      decoration: BoxDecoration(
        color: c.card,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: c.cardBorder),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 14, 8, 0),
            child: Row(
              children: [
                const Icon(Icons.bolt_rounded, color: AppColors.success, size: 20),
                const SizedBox(width: 8),
                Text(
                  'Instant Solution',
                  style: TextStyle(
                    fontWeight: FontWeight.w700,
                    fontSize: 16,
                    color: c.textPrimary,
                  ),
                ),
                const Spacer(),
                IconButton(
                  icon: Icon(
                    _showSteps ? Icons.unfold_less : Icons.unfold_more,
                    size: 20,
                    color: c.textMuted,
                  ),
                  onPressed: () => setState(() => _showSteps = !_showSteps),
                ),
              ],
            ),
          ),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 8),
              child: MarkdownBody(
                data: _showSteps
                    ? _solution!
                    : _solution!.split('\n\n').take(2).join('\n\n'),
                styleSheet: MarkdownStyleSheet(
                  p: TextStyle(fontSize: 14, height: 1.55, color: c.textPrimary),
                  strong: const TextStyle(
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
              ),
            ),
          ),
          Padding(
            padding: const EdgeInsets.fromLTRB(8, 0, 8, 8),
            child: Row(
              children: [
                TextButton.icon(
                  onPressed: _saving ? null : _saveToRevision,
                  icon: _saving
                      ? const SizedBox(
                          width: 16,
                          height: 16,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.bookmark_outline, size: 18),
                  label: Text(_saving ? 'Saving...' : 'Save'),
                ),
                const Spacer(),
                TextButton(
                  onPressed: _resetScan,
                  child: const Text('Scan again'),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  List<Widget> _cornerBrackets() {
    const size = 28.0;
    const thickness = 3.0;
    const color = AppColors.primaryLight;

    Widget bracket(Alignment align) {
      return Align(
        alignment: align,
        child: Container(
          width: size,
          height: size,
          margin: const EdgeInsets.all(20),
          decoration: BoxDecoration(
            border: Border(
              top: align == Alignment.topLeft || align == Alignment.topRight
                  ? const BorderSide(color: color, width: thickness)
                  : BorderSide.none,
              bottom: align == Alignment.bottomLeft || align == Alignment.bottomRight
                  ? const BorderSide(color: color, width: thickness)
                  : BorderSide.none,
              left: align == Alignment.topLeft || align == Alignment.bottomLeft
                  ? const BorderSide(color: color, width: thickness)
                  : BorderSide.none,
              right: align == Alignment.topRight || align == Alignment.bottomRight
                  ? const BorderSide(color: color, width: thickness)
                  : BorderSide.none,
            ),
          ),
        ),
      );
    }

    return [
      bracket(Alignment.topLeft),
      bracket(Alignment.topRight),
      bracket(Alignment.bottomLeft),
      bracket(Alignment.bottomRight),
    ];
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _scanAnim.dispose();
    _camera?.dispose();
    _tabController.removeListener(_onTabChanged);
    _tabController.dispose();
    super.dispose();
  }
}

class _ScanLineOverlay extends StatelessWidget {
  final Animation<double> animation;

  const _ScanLineOverlay({required this.animation});

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: animation,
      builder: (context, child) {
        return Align(
          alignment: Alignment(0, -1 + (animation.value * 2)),
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: 28),
            height: 2,
            decoration: BoxDecoration(
              gradient: LinearGradient(
                colors: [
                  AppColors.primary.withValues(alpha: 0),
                  AppColors.primary,
                  AppColors.primary.withValues(alpha: 0),
                ],
              ),
              boxShadow: [
                BoxShadow(
                  color: AppColors.primary.withValues(alpha: 0.6),
                  blurRadius: 8,
                ),
              ],
            ),
          ),
        );
      },
    );
  }
}
