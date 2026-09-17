import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:go_router/go_router.dart';
import 'core/theme/app_theme.dart';
import 'features/auth/auth_provider.dart';
import 'features/auth/login_screen.dart';
import 'features/members/members_screen.dart';
import 'features/members/member_detail_screen.dart';
import 'features/savings/savings_list_screen.dart';
import 'features/savings/savings_detail_screen.dart';
import 'features/loans/loans_list_screen.dart';
import 'features/loans/loan_detail_screen.dart';
import 'features/collection/savings_collection_screen.dart';
import 'features/collection/loan_collection_screen.dart';
import 'features/collection/collection_history_screen.dart';
import 'features/settlement/settlement_screen.dart';
import 'features/receipts/receipt_screen.dart';
import 'features/collection_sheet/collection_sheet_screen.dart';
import 'features/loan_application/loan_application_screen.dart';
import 'features/settings/locale_provider.dart';
import 'core/offline/sync_service.dart';

import 'features/navigation/main_navigation_screen.dart';

final goRouterProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: '/',
    redirect: (context, state) {
      final isLoggedIn = authState.status == AuthStatus.authenticated;
      final isLoginRoute = state.matchedLocation == '/login';
      if (!isLoggedIn && !isLoginRoute) return '/login';
      if (isLoggedIn && isLoginRoute) return '/';
      return null;
    },
    routes: [
      GoRoute(path: '/', builder: (context, state) => const MainNavigationScreen()),
      GoRoute(path: '/login', builder: (context, state) => const LoginScreen()),
      GoRoute(path: '/members', builder: (context, state) => const MembersScreen()),
      GoRoute(path: '/members/:id', builder: (context, state) => MemberDetailScreen(memberId: int.parse(state.pathParameters['id']!))),
      GoRoute(path: '/savings', builder: (context, state) => const SavingsListScreen()),
      GoRoute(path: '/savings/:id', builder: (context, state) => SavingsDetailScreen(accountId: int.parse(state.pathParameters['id']!))),
      GoRoute(path: '/loans', builder: (context, state) => const LoansListScreen()),
      GoRoute(path: '/loans/:id', builder: (context, state) => LoanDetailScreen(loanId: int.parse(state.pathParameters['id']!))),
      GoRoute(path: '/collection/savings/:frequency', builder: (context, state) => SavingsCollectionScreen(frequency: state.pathParameters['frequency']!)),
      GoRoute(path: '/collection/loans/:frequency', builder: (context, state) => LoanCollectionScreen(frequency: state.pathParameters['frequency']!)),
      GoRoute(path: '/collection/history', builder: (context, state) => const CollectionHistoryScreen()),
      GoRoute(path: '/collection/sheet', builder: (context, state) => const CollectionSheetScreen()),
      GoRoute(path: '/settlement', builder: (context, state) => const SettlementScreen()),
      GoRoute(path: '/loan-application', builder: (context, state) => const LoanApplicationScreen()),
      GoRoute(path: '/receipts/:type/:id', builder: (context, state) => ReceiptScreen(type: state.pathParameters['type']!, id: int.parse(state.pathParameters['id']!))),
    ],
  );
});

class EkotaApp extends ConsumerWidget {
  const EkotaApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authProvider);
    final locale = ref.watch(localeProvider);
    ref.watch(syncServiceProvider);

    return MaterialApp(
      title: 'Ekota',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      locale: locale.locale,
      supportedLocales: const [Locale('en'), Locale('bn')],
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      home: _buildHome(authState),
    );
  }

  Widget _buildHome(AuthState authState) {
    switch (authState.status) {
      case AuthStatus.loading:
      case AuthStatus.initial:
        return const Scaffold(
          body: Center(child: CircularProgressIndicator()),
        );
      case AuthStatus.authenticated:
        return const MainNavigationScreen();
      case AuthStatus.unauthenticated:
        return const LoginScreen();
    }
  }
}
