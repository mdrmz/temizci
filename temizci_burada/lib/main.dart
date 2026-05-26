import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:webview_flutter/webview_flutter.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await LocalReminderService.instance.init();
  await LocalReminderService.instance.scheduleDailyReminder();
  runApp(const TemizciBuradaApp());
}

class LocalReminderService {
  LocalReminderService._();

  static final LocalReminderService instance = LocalReminderService._();
  static const int _dailyReminderId = 7001;

  final FlutterLocalNotificationsPlugin _plugin =
      FlutterLocalNotificationsPlugin();
  bool _ready = false;

  Future<void> init() async {
    if (_ready) return;

    const androidSettings = AndroidInitializationSettings(
      '@mipmap/ic_launcher',
    );
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: false,
      requestBadgePermission: false,
      requestSoundPermission: false,
    );

    const initSettings = InitializationSettings(
      android: androidSettings,
      iOS: iosSettings,
    );

    await _plugin.initialize(
      initSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) {},
    );

    await _requestPermissions();
    _ready = true;
  }

  Future<void> _requestPermissions() async {
    final android =
        _plugin
            .resolvePlatformSpecificImplementation<
              AndroidFlutterLocalNotificationsPlugin
            >();
    await android?.requestNotificationsPermission();

    final ios =
        _plugin
            .resolvePlatformSpecificImplementation<
              IOSFlutterLocalNotificationsPlugin
            >();
    await ios?.requestPermissions(alert: true, badge: true, sound: true);
  }

  Future<void> scheduleDailyReminder() async {
    if (!_ready) {
      await init();
    }

    const androidDetails = AndroidNotificationDetails(
      'daily_reminder_channel',
      'Gunluk Hatirlatma',
      channelDescription:
          'Temizci Burada uygulamasi icin gunluk hatirlatma bildirimi',
      importance: Importance.high,
      priority: Priority.high,
    );
    const iosDetails = DarwinNotificationDetails();
    const notificationDetails = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _plugin.cancel(_dailyReminderId);
    await _plugin.periodicallyShow(
      _dailyReminderId,
      'Temizci Burada',
      'Bugun ilan ve tekliflerini kontrol etmeyi unutma.',
      RepeatInterval.daily,
      notificationDetails,
      androidScheduleMode: AndroidScheduleMode.inexactAllowWhileIdle,
      payload: 'daily_reminder',
    );
  }
}

class TemizciBuradaApp extends StatelessWidget {
  const TemizciBuradaApp({super.key});

  @override
  Widget build(BuildContext context) {
    const brandGreen = Color(0xFF10B981);
    const brandDark = Color(0xFF0F2B23);

    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'Temizci Burada',
      theme: ThemeData(
        useMaterial3: true,
        colorScheme: ColorScheme.fromSeed(
          seedColor: brandGreen,
          primary: brandGreen,
          secondary: const Color(0xFFF59E0B),
          surface: Colors.white,
        ),
        scaffoldBackgroundColor: const Color(0xFFF8FAFC),
        appBarTheme: const AppBarTheme(
          centerTitle: false,
          scrolledUnderElevation: 0,
          elevation: 0,
          backgroundColor: Colors.white,
          foregroundColor: brandDark,
          titleTextStyle: TextStyle(
            color: brandDark,
            fontSize: 17,
            fontWeight: FontWeight.w700,
          ),
        ),
      ),
      home: const SiteShell(),
    );
  }
}

class _QuickLink {
  const _QuickLink({
    required this.label,
    required this.icon,
    required this.uri,
  });

  final String label;
  final IconData icon;
  final Uri uri;
}

class SiteShell extends StatefulWidget {
  const SiteShell({super.key});

  @override
  State<SiteShell> createState() => _SiteShellState();
}

class _SiteShellState extends State<SiteShell> {
  static const Set<String> _appHosts = {
    'temizciburada.com',
    'www.temizciburada.com',
  };
  static const String _embeddedQueryKey = 'app';
  static const String _embeddedQueryValue = '1';
  static const String _embeddedCssScript = '''
(() => {
  const styleId = 'tb-app-embed-style';
  let styleTag = document.getElementById(styleId);
  if (!styleTag) {
    styleTag = document.createElement('style');
    styleTag.id = styleId;
    document.head.appendChild(styleTag);
  }
  styleTag.textContent = `
    header,
    footer,
    .mobile-quick-actions,
    .fixed.inset-x-0.bottom-0.z-40 {
      display: none !important;
      visibility: hidden !important;
      pointer-events: none !important;
      height: 0 !important;
      overflow: hidden !important;
    }
    main {
      max-width: 100% !important;
      padding-top: 12px !important;
      padding-bottom: 12px !important;
    }
    body {
      padding-bottom: 0 !important;
    }
  `;
  document.documentElement.setAttribute('data-tb-app', '1');
})();
''';
  static bool _didResetWebViewCache = false;

  static Uri _withEmbeddedQuery(Uri uri) {
    if (!_appHosts.contains(uri.host)) return uri;
    final params = Map<String, String>.from(uri.queryParameters);
    params[_embeddedQueryKey] = _embeddedQueryValue;
    return uri.replace(queryParameters: params);
  }

  static final Uri _homeUri = _withEmbeddedQuery(
    Uri.parse('https://temizciburada.com/'),
  );
  static final List<_QuickLink> _quickLinks = [
    _QuickLink(
      label: 'Ana Sayfa',
      icon: Icons.home_outlined,
      uri: _withEmbeddedQuery(Uri.parse('https://temizciburada.com/')),
    ),
    _QuickLink(
      label: 'Ilanlar',
      icon: Icons.view_list_outlined,
      uri: _withEmbeddedQuery(Uri.parse('https://temizciburada.com/listings')),
    ),
    _QuickLink(
      label: 'Teklifler',
      icon: Icons.local_offer_outlined,
      uri: _withEmbeddedQuery(Uri.parse('https://temizciburada.com/offers')),
    ),
    _QuickLink(
      label: 'Panel',
      icon: Icons.dashboard_outlined,
      uri: _withEmbeddedQuery(Uri.parse('https://temizciburada.com/dashboard')),
    ),
    _QuickLink(
      label: 'Profil',
      icon: Icons.person_outline,
      uri: _withEmbeddedQuery(Uri.parse('https://temizciburada.com/profile')),
    ),
  ];
  static const List<(String label, String path)> _menuLinks = [
    ('Giris', '/login'),
    ('Kayit', '/register'),
    ('Yeni ilan', '/listings/new'),
    ('Evlerim', '/homes'),
    ('Ilanlar', '/listings'),
    ('Teklifler', '/offers'),
    ('Panel', '/dashboard'),
    ('Profil', '/profile'),
  ];

  // FIX: late final yerine nullable — didChangeDependencies'te init edilecek
  WebViewController? _controller;
  bool _controllerReady = false;

  int _loadingProgress = 0;
  bool _canGoBack = false;
  bool _hasMainFrameError = false;
  String _errorMessage = 'Baglanti kurulurken bir sorun olustu.';
  int _selectedIndex = 0;
  Timer? _loadWatchdog;
  bool _connectionSheetOpen = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    // Sadece bir kez init et
    if (_controllerReady) return;
    _controllerReady = true;
    _initController();
  }

  // FIX: Controller init'i ayrı metoda alındı, main thread'e post edildi
  void _initController() {
    WidgetsBinding.instance.addPostFrameCallback((_) async {
      if (!mounted) return;

      const params = PlatformWebViewControllerCreationParams();
      final controller = WebViewController.fromPlatformCreationParams(params);

      if (!_didResetWebViewCache) {
        await _resetWebViewCache(controller);
        _didResetWebViewCache = true;
      }

      controller
        ..setJavaScriptMode(JavaScriptMode.unrestricted)
        ..setUserAgent('TemizciBuradaApp/1.0')
        ..setBackgroundColor(Colors.white)
        ..setNavigationDelegate(
          NavigationDelegate(
            onProgress: (int progress) {
              if (!mounted) return;
              setState(() => _loadingProgress = progress);
            },
            onPageStarted: (String url) {
              if (!mounted) return;
              _startLoadWatchdog();
              final uri = Uri.tryParse(url);
              setState(() {
                _hasMainFrameError = false;
                _loadingProgress = 0;
                if (uri != null) {
                  _selectedIndex = _indexForPath(uri.path);
                }
              });
            },
            onPageFinished: (String url) async {
              _stopLoadWatchdog();
              await _applyEmbeddedChrome(controller);
              final canGoBack = await controller.canGoBack();
              if (!mounted) return;
              setState(() {
                _loadingProgress = 100;
                _canGoBack = canGoBack;
              });
            },
            onUrlChange: (UrlChange change) {
              final uri = Uri.tryParse(change.url ?? '');
              if (uri == null || !mounted) return;
              unawaited(_applyEmbeddedChrome(controller));
              setState(() {
                _selectedIndex = _indexForPath(uri.path);
              });
            },
            onWebResourceError: (WebResourceError error) {
              _stopLoadWatchdog();
              if (error.isForMainFrame != false) {
                if (!mounted) return;
                final message =
                    error.description.trim().isEmpty
                        ? 'Baglanti kurulurken bir sorun olustu.'
                        : error.description;
                setState(() {
                  _hasMainFrameError = true;
                  _errorMessage = message;
                });
                _showConnectionSheet(
                  title: 'Baglanti sorunu',
                  message: message,
                );
              }
            },
            onNavigationRequest: (NavigationRequest request) {
              if (!request.isMainFrame) return NavigationDecision.navigate;
              final uri = Uri.tryParse(request.url);
              if (uri == null) return NavigationDecision.prevent;
              final isWeb = uri.scheme == 'https' || uri.scheme == 'http';
              if (!isWeb) return NavigationDecision.prevent;

              if (_shouldOpenInApp(uri)) {
                final embeddedUri = _withEmbeddedQuery(uri);
                if (embeddedUri.toString() != uri.toString()) {
                  unawaited(controller.loadRequest(embeddedUri));
                  return NavigationDecision.prevent;
                }
              }

              return NavigationDecision.navigate;
            },
          ),
        )
        ..loadRequest(_homeUri);

      if (!mounted) return;
      setState(() => _controller = controller);
    });
  }

  int _indexForPath(String path) {
    if (path == '/' || path.isEmpty) return 0;
    if (path.startsWith('/listings')) return 1;
    if (path.startsWith('/offers')) return 2;
    if (path.startsWith('/dashboard')) return 3;
    if (path.startsWith('/profile')) return 4;
    return 0;
  }

  bool _shouldOpenInApp(Uri uri) {
    final isWeb = uri.scheme == 'https' || uri.scheme == 'http';
    return isWeb && _appHosts.contains(uri.host);
  }

  Future<void> _syncNavigationState() async {
    final ctrl = _controller;
    if (ctrl == null) return;
    final canGoBack = await ctrl.canGoBack();
    final currentUrl = await ctrl.currentUrl();
    if (!mounted) return;
    setState(() {
      _canGoBack = canGoBack;
      if (currentUrl != null) {
        final uri = Uri.tryParse(currentUrl);
        if (uri != null) {
          _selectedIndex = _indexForPath(uri.path);
        }
      }
    });
  }

  Future<void> _goBack() async {
    final ctrl = _controller;
    if (ctrl == null) return;
    if (await ctrl.canGoBack()) {
      await ctrl.goBack();
      await _syncNavigationState();
    }
  }

  // FIX: SystemNavigator.pop() iOS'ta crash yapıyor — kaldırıldı
  Future<void> _handleSystemBack() async {
    final ctrl = _controller;
    if (ctrl == null) return;
    if (await ctrl.canGoBack()) {
      await ctrl.goBack();
      await _syncNavigationState();
    }
  }

  Future<void> _reload() async {
    _stopLoadWatchdog();
    if (!mounted) return;
    setState(() => _hasMainFrameError = false);
    await _controller?.reload();
  }

  Future<void> _goHome() async {
    _stopLoadWatchdog();
    if (!mounted) return;
    setState(() => _hasMainFrameError = false);
    await _controller?.loadRequest(_homeUri);
  }

  Future<void> _goToQuickLink(int index) async {
    _stopLoadWatchdog();
    if (!mounted) return;
    setState(() {
      _selectedIndex = index;
      _hasMainFrameError = false;
    });
    await _controller?.loadRequest(_quickLinks[index].uri);
  }

  Future<void> _goToPath(String path) async {
    _stopLoadWatchdog();
    final normalized = path.startsWith('/') ? path : '/$path';
    final uri = _withEmbeddedQuery(
      Uri.parse('https://temizciburada.com$normalized'),
    );
    setState(() {
      _hasMainFrameError = false;
      _selectedIndex = _indexForPath(normalized);
    });
    await _controller?.loadRequest(uri);
  }

  Future<void> _applyEmbeddedChrome(WebViewController controller) async {
    try {
      await controller.runJavaScript(_embeddedCssScript);
    } catch (err) {
      if (kDebugMode) {
        debugPrint('Embed stil uygulanamadi: $err');
      }
    }
  }

  Future<void> _resetWebViewCache(WebViewController controller) async {
    try {
      final cookieManager = WebViewCookieManager();
      await controller.clearCache();
      await controller.clearLocalStorage();
      await cookieManager.clearCookies();
    } catch (err) {
      if (kDebugMode) {
        debugPrint('WebView cache sifirlanamadi: $err');
      }
    }
  }

  Future<void> _refreshDailyReminder() async {
    try {
      await LocalReminderService.instance.scheduleDailyReminder();
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Gunluk bildirim aktif edildi.'),
          duration: Duration(seconds: 2),
        ),
      );
    } catch (err) {
      if (kDebugMode) {
        debugPrint('Bildirim ayarlanamadi: $err');
      }
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Bildirim izni kapali olabilir.'),
          duration: Duration(seconds: 2),
        ),
      );
    }
  }

  void _startLoadWatchdog() {
    _stopLoadWatchdog();
    _loadWatchdog = Timer(const Duration(seconds: 18), () {
      if (!mounted || _loadingProgress >= 100) return;
      const message =
          'Sayfa beklenenden uzun surdu. Internet baglantinizi kontrol edip tekrar deneyin.';
      setState(() {
        _hasMainFrameError = true;
        _errorMessage = message;
      });
      _showConnectionSheet(title: 'Yukleme gecikmesi', message: message);
    });
  }

  void _stopLoadWatchdog() {
    _loadWatchdog?.cancel();
    _loadWatchdog = null;
  }

  void _showConnectionSheet({required String title, required String message}) {
    if (!mounted || _connectionSheetOpen) return;
    _connectionSheetOpen = true;
    showModalBottomSheet<void>(
      context: context,
      showDragHandle: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(18)),
      ),
      builder: (context) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 8, 16, 16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: const [
                    Icon(Icons.wifi_off_rounded, color: Color(0xFFB91C1C)),
                    SizedBox(width: 8),
                    Text(
                      'Baglanti bildirimi',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  title,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w700,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  message,
                  style: const TextStyle(
                    fontSize: 13,
                    height: 1.4,
                    color: Color(0xFF475569),
                  ),
                ),
                const SizedBox(height: 14),
                Row(
                  children: [
                    Expanded(
                      child: FilledButton.icon(
                        onPressed: () {
                          Navigator.of(context).pop();
                          _reload();
                        },
                        icon: const Icon(Icons.refresh),
                        label: const Text('Tekrar dene'),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.of(context).pop();
                          _goHome();
                        },
                        icon: const Icon(Icons.home_outlined),
                        label: const Text('Ana sayfa'),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    ).whenComplete(() {
      _connectionSheetOpen = false;
    });
  }

  String _appBarTitle() => _quickLinks[_selectedIndex].label;

  @override
  void dispose() {
    _stopLoadWatchdog();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final ctrl = _controller;

    return PopScope(
      canPop: false,
      onPopInvokedWithResult: (bool didPop, _) {
        if (!didPop) _handleSystemBack();
      },
      child: Scaffold(
        appBar: AppBar(
          toolbarHeight: 52,
          titleSpacing: 0,
          leading: IconButton(
            tooltip: 'Geri',
            onPressed: _canGoBack ? _goBack : null,
            icon: const Icon(Icons.arrow_back),
          ),
          title: Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(6),
                child: Image.asset(
                  'assets/logo.png',
                  height: 28,
                  width: 28,
                  fit: BoxFit.cover,
                  errorBuilder:
                      (_, __, ___) => const Icon(
                        Icons.cleaning_services_outlined,
                        size: 24,
                      ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  _appBarTitle(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          actions: [
            PopupMenuButton<String>(
              tooltip: 'Hizli menu',
              onSelected: _goToPath,
              itemBuilder: (context) {
                return _menuLinks
                    .map(
                      (item) => PopupMenuItem<String>(
                        value: item.$2,
                        child: Text(item.$1),
                      ),
                    )
                    .toList();
              },
              icon: const Icon(Icons.tune),
            ),
            IconButton(
              tooltip: 'Yenile',
              onPressed: _reload,
              icon: const Icon(Icons.refresh),
            ),
            if (_hasMainFrameError)
              IconButton(
                tooltip: 'Baglanti bildirimi',
                onPressed:
                    () => _showConnectionSheet(
                      title: 'Baglanti sorunu',
                      message: _errorMessage,
                    ),
                icon: const Icon(Icons.error_outline),
              ),
            IconButton(
              tooltip: 'Gunluk bildirim',
              onPressed: _refreshDailyReminder,
              icon: const Icon(Icons.notifications_active_outlined),
            ),
          ],
          bottom: PreferredSize(
            preferredSize: const Size.fromHeight(2),
            child: SizedBox(
              height: 2,
              child:
                  _loadingProgress < 100
                      ? LinearProgressIndicator(value: _loadingProgress / 100)
                      : const SizedBox.shrink(),
            ),
          ),
        ),
        body: Stack(
          children: [
            // FIX: controller null iken loading göster, beyaz ekran olmasın
            if (ctrl == null)
              const Center(child: CircularProgressIndicator())
            else
              WebViewWidget(controller: ctrl),
            if (_hasMainFrameError)
              ConnectionErrorView(
                message: _errorMessage,
                onRetry: _reload,
                onGoHome: _goHome,
              ),
          ],
        ),
        bottomNavigationBar: NavigationBar(
          selectedIndex: _selectedIndex,
          onDestinationSelected: _goToQuickLink,
          destinations:
              _quickLinks
                  .map(
                    (link) => NavigationDestination(
                      icon: Icon(link.icon),
                      label: link.label,
                    ),
                  )
                  .toList(),
        ),
      ),
    );
  }
}

class ConnectionErrorView extends StatelessWidget {
  const ConnectionErrorView({
    super.key,
    required this.message,
    required this.onRetry,
    required this.onGoHome,
  });

  final String message;
  final VoidCallback onRetry;
  final VoidCallback onGoHome;

  @override
  Widget build(BuildContext context) {
    final colorScheme = Theme.of(context).colorScheme;

    return ColoredBox(
      color: Colors.white,
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 420),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  Icons.wifi_off_rounded,
                  color: colorScheme.primary,
                  size: 54,
                ),
                const SizedBox(height: 16),
                const Text(
                  'Baglanti sorunu',
                  textAlign: TextAlign.center,
                  style: TextStyle(fontSize: 21, fontWeight: FontWeight.w800),
                ),
                const SizedBox(height: 10),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: const TextStyle(
                    fontSize: 14,
                    color: Color(0xFF64748B),
                    height: 1.45,
                  ),
                ),
                const SizedBox(height: 20),
                Wrap(
                  alignment: WrapAlignment.center,
                  spacing: 10,
                  runSpacing: 10,
                  children: [
                    FilledButton.icon(
                      onPressed: onRetry,
                      icon: const Icon(Icons.refresh),
                      label: const Text('Tekrar dene'),
                    ),
                    OutlinedButton.icon(
                      onPressed: onGoHome,
                      icon: const Icon(Icons.home_outlined),
                      label: const Text('Ana sayfaya don'),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
