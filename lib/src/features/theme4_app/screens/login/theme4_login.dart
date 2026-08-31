import 'package:flutter/material.dart';

import '../../app/theme.dart';
import '../../../../../main.dart';

/// The entry screen. None of the buttons authenticate — any login option simply
/// fades through to the [MainShell].
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  bool _agreed = false;

  void _continue() {
    if (!_agreed) {
      _nudgeAgreement();
      return;
    }
    Navigator.of(context).pushReplacement(PageRouteBuilder(
      transitionDuration: const Duration(milliseconds: 500),
      pageBuilder: (context, animation, secondaryAnimation) => const MainShell(),
      transitionsBuilder: (context, anim, secondaryAnimation, child) =>
          FadeTransition(opacity: anim, child: child),
    ));
  }

  void _nudgeAgreement() {
    ScaffoldMessenger.of(context).showSnackBar(SnackBar(
      behavior: SnackBarBehavior.floating,
      backgroundColor: AppColors.ink,
      content: const Text('Please agree to the Terms & Privacy Policy first.'),
    ));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFF0D0033),
      body: Stack(
        fit: StackFit.expand,
        children: [
          // Background
          Image.asset(
            'assets/images/login/login_bg.webp',
            fit: BoxFit.cover,
          ),

          // Top Image with bottom blend
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: ShaderMask(
              shaderCallback: (rect) {
                return const LinearGradient(
                  begin: Alignment.topCenter,
                  end: Alignment.bottomCenter,
                  colors: [Colors.black, Colors.transparent],
                  stops: [0.7, 1],
                ).createShader(rect);
              },
              blendMode: BlendMode.dstIn,
              child: Image.asset(
                'assets/images/login/login_top_img.webp',
                fit: BoxFit.fitWidth,
                opacity: const AlwaysStoppedAnimation(0.5),
              ),
            ),
          ),

          // Top Shade Overlay
          Positioned(
            top: 0,
            left: 0,
            right: 0,
            child: Image.asset(
              'assets/images/login/login_top_shade.webp',
              fit: BoxFit.fitWidth,
            ),
          ),

          // Content
          SafeArea(
            child: Column(
              children: [
                const Spacer(flex: 16),
                _Branding(),
                const Spacer(flex: 10),
                _LoginButtons(onLogin: _continue),
                const Spacer(flex: 6),
                _Agreement(
                  value: _agreed,
                  onChanged: (v) => setState(() => _agreed = v ?? false),
                ),
                const SizedBox(height: 20),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _Branding extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Stack(children: [
      Image.asset(
        'assets/images/login/logo_1.webp',
        width: 300,
      ),
      Positioned(
          bottom: 24,
          right: 8,
          child: Image.asset('assets/images/login/logo_2.webp')
      )
    ],);
  }
}

class _LoginButtons extends StatelessWidget {
  final VoidCallback onLogin;
  const _LoginButtons({required this.onLogin});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 40),
      child: Column(
        children: [
          _SocialButton(
            label: 'Continue with Google',
            iconPath: 'assets/images/google.png', // Placeholder, using logo as icon for now if needed, or stick to Icons
            onPressed: onLogin,
            isGoogle: true,
          ),
          const SizedBox(height: 20),
          _SocialButton(
            label: 'Log in with ID',
            iconPath: 'assets/images/login/logo_2.webp', // Placeholder
            onPressed: onLogin,
          ),
          const SizedBox(height: 40),
          // Circular Phone Button
          GestureDetector(
            onTap: onLogin,
            child: Container(
              width: 56,
              height: 56,
              decoration: const BoxDecoration(
                color: Colors.white,
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.phone_iphone_rounded, color: Colors.black, size: 28),
            ),
          ),
        ],
      ),
    );
  }
}

class _SocialButton extends StatelessWidget {
  final String label;
  final String? iconPath;
  final VoidCallback onPressed;
  final bool isGoogle;

  const _SocialButton({
    required this.label,
    this.iconPath,
    required this.onPressed,
    this.isGoogle = false,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 60,
      child: ElevatedButton(
        onPressed: onPressed,
        style: ElevatedButton.styleFrom(
          backgroundColor: Colors.white,
          foregroundColor: Colors.black,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(30)),
          elevation: 0,
        ),
        child: Row(
          children: [
            const SizedBox(width: 10),
            if (isGoogle)
              const Icon(Icons.g_mobiledata_rounded, size: 40, color: Colors.blue)
            else
              const Icon(Icons.badge_outlined, size: 30, color: Colors.black),
            Expanded(
              child: Center(
                child: Text(
                  label,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ),
            const SizedBox(width: 40), // Balance the icon space
          ],
        ),
      ),
    );
  }
}

class _Agreement extends StatelessWidget {
  final bool value;
  final ValueChanged<bool?> onChanged;
  const _Agreement({required this.value, required this.onChanged});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Theme(
              data: ThemeData(unselectedWidgetColor: Colors.white),
              child: SizedBox(
                width: 24,
                height: 24,
                child: Checkbox(
                  value: value,
                  onChanged: onChanged,
                  activeColor: const Color(0xFFC046FF),
                  shape: const CircleBorder(),
                  side: const BorderSide(color: Colors.white54, width: 2),
                ),
              ),
            ),
            const SizedBox(width: 8),
            const Text(
              'I have read and agreed on',
              style: TextStyle(color: Colors.white70, fontSize: 13),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text.rich(
          TextSpan(
            style: const TextStyle(fontSize: 13, color: Colors.white70),
            children: [
              const TextSpan(text: 'Tocco Chat '),
              TextSpan(
                text: 'Terms of Service',
                style: const TextStyle(color: Color(0xFFA678FF), fontWeight: FontWeight.w600),
              ),
              const TextSpan(text: ' and '),
              TextSpan(
                text: 'Privacy Policy',
                style: const TextStyle(color: Color(0xFFA678FF), fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
