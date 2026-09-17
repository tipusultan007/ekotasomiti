import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../shared/helpers/app_strings.dart';
import '../../shared/widgets/app_logo.dart';
import '../../shared/widgets/language_switch_button.dart';
import 'auth_provider.dart';

class LoginScreen extends ConsumerStatefulWidget {
  const LoginScreen({super.key});

  @override
  ConsumerState<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends ConsumerState<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;

  @override
  void dispose() {
    _phoneController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _login() {
    if (_formKey.currentState!.validate()) {
      ref.read(authProvider.notifier).login(
            _phoneController.text.trim(),
            _passwordController.text,
          );
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);
    final isBn = context.isBn;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        actions: const [
          Padding(
            padding: EdgeInsets.only(right: 16),
            child: LanguageSwitchButton(),
          ),
        ],
      ),
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(24),
            child: Form(
              key: _formKey,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const AppLogo(size: 88, borderRadius: 20),
                  const SizedBox(height: 18),
                  Text(
                    isBn ? 'একতা' : 'Ekota',
                    style: Theme.of(context).textTheme.headlineMedium?.copyWith(
                          fontWeight: FontWeight.w900,
                          color: const Color(0xFF0F172A),
                          letterSpacing: 0.5,
                        ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    isBn ? 'ফিল্ড অফিসার ও সঞ্চয়-ঋণ পোর্টাল' : 'Field Officer & Microfinance Portal',
                    style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                          color: Colors.grey[600],
                          fontWeight: FontWeight.w600,
                        ),
                  ),
                  const SizedBox(height: 36),
                  TextFormField(
                    controller: _phoneController,
                    keyboardType: TextInputType.phone,
                    decoration: InputDecoration(
                      labelText: isBn ? 'মোবাইল নম্বর বা ইমেইল' : 'Phone Number or Email',
                      hintText: isBn ? '০১৭XXXXXXXX' : '017XXXXXXXX',
                      prefixIcon: const Icon(Icons.phone_android_rounded),
                      filled: true,
                      fillColor: Colors.white,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                    ),
                    validator: (v) => v == null || v.isEmpty ? (isBn ? 'মোবাইল নম্বর আবশ্যক' : 'Phone number is required') : null,
                  ),
                  const SizedBox(height: 16),
                  TextFormField(
                    controller: _passwordController,
                    obscureText: _obscurePassword,
                    decoration: InputDecoration(
                      labelText: isBn ? 'পাসওয়ার্ড' : 'Password',
                      prefixIcon: const Icon(Icons.lock_outline_rounded),
                      filled: true,
                      fillColor: Colors.white,
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: BorderSide(color: Colors.grey.shade300)),
                      suffixIcon: IconButton(
                        icon: Icon(_obscurePassword ? Icons.visibility_rounded : Icons.visibility_off_rounded),
                        onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                      ),
                    ),
                    validator: (v) => v == null || v.isEmpty ? (isBn ? 'পাসওয়ার্ড আবশ্যক' : 'Password is required') : null,
                  ),
                  if (authState.error != null) ...[
                    const SizedBox(height: 16),
                    Text(authState.error!, style: const TextStyle(color: Colors.red, fontWeight: FontWeight.bold), textAlign: TextAlign.center),
                  ],
                  const SizedBox(height: 24),
                  SizedBox(
                    width: double.infinity,
                    height: 50,
                    child: ElevatedButton(
                      onPressed: authState.status == AuthStatus.loading ? null : _login,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: const Color(0xFF0F172A),
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 2,
                      ),
                      child: authState.status == AuthStatus.loading
                          ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : Text(isBn ? 'লগইন করুন' : 'Sign In', style: const TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
