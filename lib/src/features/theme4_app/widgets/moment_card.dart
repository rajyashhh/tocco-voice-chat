import 'package:flutter/material.dart';

import '../app/theme.dart';
import '../models/post.dart';
import 'coming_soon.dart';

/// A single feed post on the Moment screen matching the requested design.
class MomentCard extends StatefulWidget {
  final Post post;
  const MomentCard({super.key, required this.post});

  @override
  State<MomentCard> createState() => _MomentCardState();
}

class _MomentCardState extends State<MomentCard> {
  int _likes = 3;

  @override
  Widget build(BuildContext context) {
    final post = widget.post;

    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(15),
                child: Image.asset(
                  'assets/images/home/profile_dp.webp', // Matching the image in screenshot
                  width: 65,
                  height: 65,
                  fit: BoxFit.cover,
                  errorBuilder: (_, __, ___) => Image.asset(post.avatar, width: 65, height: 65, fit: BoxFit.cover),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      post.author,
                      style: const TextStyle(fontSize: 19, fontWeight: FontWeight.bold, color: Color(0xFF1E1B2E)),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        // Gender/Age Badge
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(colors: [Color(0xFFFFB7D5), Color(0xFFFF8EC7)]),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: const Row(
                            children: [
                              Icon(Icons.female, size: 12, color: Colors.white),
                              SizedBox(width: 2),
                              Text('25', style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.bold)),
                            ],
                          ),
                        ),
                        const SizedBox(width: 6),
                        // Level Tag Asset
                        Image.asset('assets/images/home/level_tag.webp', height: 18),
                      ],
                    ),
                  ],
                ),
              ),
              // Hi Button Asset
              GestureDetector(
                onTap: () => showComingSoon(context, 'Say Hi to ${post.author}'),
                child: Image.asset('assets/images/home/hi_button.webp', width: 75),
              ),
            ],
          ),
          
          const SizedBox(height: 20),
          
          // Post Content
          Padding(
            padding: const EdgeInsets.only(left: 18),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Wǒ ài nǐmen',
                  style: TextStyle(fontSize: 15, fontWeight: FontWeight.w500, color: Color(0xFF1E1B2E)),
                ),
                const SizedBox(height: 12),
                ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: Image.asset(
                    'assets/images/home/momentcard_pic.webp',
                    width: 130,
                    height: 130,
                    fit: BoxFit.cover,
                  ),
                ),
                const SizedBox(height: 12),
                Text(
                  '4 Days ago .1331km',
                  style: TextStyle(fontSize: 13, color: Colors.black.withValues(alpha: 0.4), fontWeight: FontWeight.w500),
                ),
                const SizedBox(height: 16),
                
                // Footer Actions
                Row(
                  children: [
                    const Icon(Icons.more_horiz_rounded, color: Color(0xFF1E1B2E), size: 30),
                    const Spacer(),
                    GestureDetector(
                      onTap: () => setState(() => _likes++),
                      child: Row(
                        children: [
                          const Icon(Icons.thumb_up_alt_outlined, color: Color(0xFF1E1B2E), size: 26),
                          const SizedBox(width: 8),
                          Text('$_likes', style: const TextStyle(fontSize: 18, color: Color(0xFF1E1B2E), fontWeight: FontWeight.bold)),
                        ],
                      ),
                    ),
                    const SizedBox(width: 30),
                    const Icon(Icons.edit_note_rounded, color: Color(0xFF1E1B2E), size: 32),
                  ],
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
