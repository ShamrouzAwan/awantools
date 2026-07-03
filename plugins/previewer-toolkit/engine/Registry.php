<?php
defined('AWAN') or die('Direct access denied.');

class PT_Registry
{
    static function categories(): array
    {
        return [
            'og' => [
                'name'           => 'OG Images',
                'icon'           => 'share-nodes',
                'description'    => 'Open Graph images for social sharing',
                'default_width'  => 1200,
                'default_height' => 630,
                'templates'      => [
                    'github_dark'   => ['name' => 'GitHub Dark',    'desc' => 'Dark GitHub-style card'],
                    'github_light'  => ['name' => 'GitHub Light',   'desc' => 'Light GitHub-style card'],
                    'glassmorphism' => ['name' => 'Glassmorphism',  'desc' => 'Frosted glass on gradient'],
                    'gradient'      => ['name' => 'Gradient Modern','desc' => 'Bold gradient background'],
                    'minimal'       => ['name' => 'Minimal',        'desc' => 'Clean minimal design'],
                    'neon'          => ['name' => 'Neon Glow',      'desc' => 'Dark with neon effects'],
                ],
                'defaults' => [
                    'github_dark'   => ['bg_color' => '0d1117', 'heading_color' => 'ffffff', 'description_color' => '94a3b8', 'accent_color' => '22c55e'],
                    'github_light'  => ['bg_color' => 'ffffff', 'heading_color' => '111827', 'description_color' => '6b7280', 'accent_color' => '4f46e5'],
                    'glassmorphism' => ['bg_color' => '1e1b4b', 'heading_color' => 'ffffff', 'description_color' => 'c4b5fd', 'accent_color' => 'a78bfa'],
                    'gradient'      => ['bg_color' => '0ea5e9', 'heading_color' => 'ffffff', 'description_color' => 'e0f2fe', 'accent_color' => 'fbbf24'],
                    'minimal'       => ['bg_color' => 'f8fafc', 'heading_color' => '0f172a', 'description_color' => '475569', 'accent_color' => '4f46e5'],
                    'neon'          => ['bg_color' => '030712', 'heading_color' => '4ade80', 'description_color' => '6ee7b7', 'accent_color' => '4ade80'],
                ],
            ],
            'social' => [
                'name'           => 'Social Cards',
                'icon'           => 'users',
                'description'    => 'Cards for Twitter, LinkedIn, and more',
                'default_width'  => 1200,
                'default_height' => 675,
                'templates'      => [
                    'twitter_dark'  => ['name' => 'Twitter Dark',   'desc' => 'Twitter/X dark card style'],
                    'linkedin'      => ['name' => 'LinkedIn',        'desc' => 'Professional LinkedIn card'],
                    'modern_dark'   => ['name' => 'Modern Dark',     'desc' => 'Modern bold dark card'],
                    'split'         => ['name' => 'Split Layout',    'desc' => 'Two-column split design'],
                    'corporate'     => ['name' => 'Corporate',       'desc' => 'Clean corporate style'],
                ],
                'defaults' => [
                    'twitter_dark'  => ['bg_color' => '15202b', 'heading_color' => 'ffffff', 'description_color' => '8b98a5', 'accent_color' => '1d9bf0'],
                    'linkedin'      => ['bg_color' => '0a66c2', 'heading_color' => 'ffffff', 'description_color' => 'e7f0f9', 'accent_color' => 'ffffff'],
                    'modern_dark'   => ['bg_color' => '18181b', 'heading_color' => 'ffffff', 'description_color' => 'a1a1aa', 'accent_color' => 'f97316'],
                    'split'         => ['bg_color' => 'f8fafc', 'heading_color' => '0f172a', 'description_color' => '64748b', 'accent_color' => '4f46e5'],
                    'corporate'     => ['bg_color' => 'ffffff', 'heading_color' => '1e293b', 'description_color' => '475569', 'accent_color' => '2563eb'],
                ],
            ],
            'placeholder' => [
                'name'           => 'Placeholder',
                'icon'           => 'image',
                'description'    => 'Placeholder images for design mockups',
                'default_width'  => 800,
                'default_height' => 600,
                'templates'      => [
                    'simple'    => ['name' => 'Simple',    'desc' => 'Plain placeholder with size'],
                    'grid'      => ['name' => 'Grid',      'desc' => 'Grid pattern placeholder'],
                    'glass'     => ['name' => 'Glass',     'desc' => 'Glass effect placeholder'],
                    'gradient'  => ['name' => 'Gradient',  'desc' => 'Gradient placeholder'],
                    'pattern'   => ['name' => 'Pattern',   'desc' => 'Geometric pattern'],
                ],
                'defaults' => [
                    'simple'    => ['bg_color' => 'e2e8f0', 'heading_color' => '94a3b8', 'accent_color' => '94a3b8'],
                    'grid'      => ['bg_color' => 'f1f5f9', 'heading_color' => '94a3b8', 'accent_color' => 'cbd5e1'],
                    'glass'     => ['bg_color' => '0f172a', 'heading_color' => 'ffffff', 'accent_color' => '38bdf8'],
                    'gradient'  => ['bg_color' => '4f46e5', 'heading_color' => 'ffffff', 'accent_color' => 'a78bfa'],
                    'pattern'   => ['bg_color' => '1e293b', 'heading_color' => 'ffffff', 'accent_color' => '38bdf8'],
                ],
            ],
            'github' => [
                'name'           => 'GitHub Cards',
                'icon'           => 'github',
                'description'    => 'GitHub README repository cards',
                'default_width'  => 1200,
                'default_height' => 600,
                'templates'      => [
                    'repo_dark'  => ['name' => 'Repo Dark',   'desc' => 'Dark repository card'],
                    'repo_light' => ['name' => 'Repo Light',  'desc' => 'Light repository card'],
                    'stats'      => ['name' => 'Stats',        'desc' => 'Repository statistics card'],
                    'compact'    => ['name' => 'Compact',      'desc' => 'Compact repository card'],
                    'gradient'   => ['name' => 'Gradient',     'desc' => 'Gradient repository card'],
                ],
                'defaults' => [
                    'repo_dark'  => ['bg_color' => '0d1117', 'heading_color' => 'ffffff', 'description_color' => '8b949e', 'accent_color' => '238636'],
                    'repo_light' => ['bg_color' => 'ffffff', 'heading_color' => '24292f', 'description_color' => '57606a', 'accent_color' => '0969da'],
                    'stats'      => ['bg_color' => '161b22', 'heading_color' => 'e6edf3', 'description_color' => '8b949e', 'accent_color' => '58a6ff'],
                    'compact'    => ['bg_color' => '0d1117', 'heading_color' => 'e6edf3', 'description_color' => '8b949e', 'accent_color' => 'f85149'],
                    'gradient'   => ['bg_color' => '4f46e5', 'heading_color' => 'ffffff', 'description_color' => 'c7d2fe', 'accent_color' => 'fbbf24'],
                ],
            ],
            'browser' => [
                'name'           => 'Browser Mockups',
                'icon'           => 'globe',
                'description'    => 'Browser window screenshots',
                'default_width'  => 1400,
                'default_height' => 900,
                'templates'      => [
                    'chrome_dark'  => ['name' => 'Chrome Dark',   'desc' => 'Chrome dark theme'],
                    'chrome_light' => ['name' => 'Chrome Light',  'desc' => 'Chrome light theme'],
                    'safari'       => ['name' => 'Safari',         'desc' => 'Safari macOS style'],
                    'minimal'      => ['name' => 'Minimal',        'desc' => 'Minimal browser frame'],
                ],
                'defaults' => [
                    'chrome_dark'  => ['bg_color' => '1f1f1f', 'heading_color' => 'ffffff', 'accent_color' => '4285f4', 'url' => 'https://awantools.site'],
                    'chrome_light' => ['bg_color' => 'f1f3f4', 'heading_color' => '202124', 'accent_color' => '1a73e8', 'url' => 'https://awantools.site'],
                    'safari'       => ['bg_color' => 'ececec', 'heading_color' => '1d1d1f', 'accent_color' => '007aff', 'url' => 'https://awantools.site'],
                    'minimal'      => ['bg_color' => '18181b', 'heading_color' => 'ffffff', 'accent_color' => '6366f1', 'url' => 'https://awantools.site'],
                ],
            ],
            'terminal' => [
                'name'           => 'Terminal',
                'icon'           => 'terminal',
                'description'    => 'Terminal window screenshots',
                'default_width'  => 1100,
                'default_height' => 660,
                'templates'      => [
                    'macos'   => ['name' => 'macOS',    'desc' => 'macOS terminal style'],
                    'linux'   => ['name' => 'Linux',    'desc' => 'Linux terminal style'],
                    'dark'    => ['name' => 'Dark Pro',  'desc' => 'Professional dark terminal'],
                    'minimal' => ['name' => 'Minimal',  'desc' => 'Minimal terminal frame'],
                ],
                'defaults' => [
                    'macos'   => ['bg_color' => '1e1e2e', 'heading_color' => 'cdd6f4', 'description_color' => '6c7086', 'accent_color' => 'a6e3a1', 'prompt' => '~ $ npm install && npm run dev'],
                    'linux'   => ['bg_color' => '0c0c0c', 'heading_color' => 'ffffff', 'description_color' => '00ff00', 'accent_color' => '00ff00', 'prompt' => 'user@server:~$ ls -la'],
                    'dark'    => ['bg_color' => '1a1a2e', 'heading_color' => 'e2e8f0', 'description_color' => '94a3b8', 'accent_color' => '38bdf8', 'prompt' => '❯ git push origin main'],
                    'minimal' => ['bg_color' => '111111', 'heading_color' => 'ffffff', 'description_color' => '888888', 'accent_color' => 'aaaaaa', 'prompt' => '$ echo "Hello, World!"'],
                ],
            ],
            'profile' => [
                'name'           => 'Profile Cards',
                'icon'           => 'user',
                'description'    => 'Profile and team member cards',
                'default_width'  => 800,
                'default_height' => 460,
                'templates'      => [
                    'minimal'   => ['name' => 'Minimal',   'desc' => 'Clean minimal profile'],
                    'modern'    => ['name' => 'Modern',    'desc' => 'Modern with gradient'],
                    'dark'      => ['name' => 'Dark',      'desc' => 'Dark theme profile'],
                    'glass'     => ['name' => 'Glass',     'desc' => 'Glassmorphism profile'],
                    'corporate' => ['name' => 'Corporate', 'desc' => 'Professional profile'],
                ],
                'defaults' => [
                    'minimal'   => ['bg_color' => 'ffffff', 'heading_color' => '111827', 'description_color' => '6b7280', 'accent_color' => '4f46e5'],
                    'modern'    => ['bg_color' => '7c3aed', 'heading_color' => 'ffffff', 'description_color' => 'e9d5ff', 'accent_color' => 'fbbf24'],
                    'dark'      => ['bg_color' => '111827', 'heading_color' => 'ffffff', 'description_color' => '9ca3af', 'accent_color' => '06b6d4'],
                    'glass'     => ['bg_color' => '1e1b4b', 'heading_color' => 'ffffff', 'description_color' => 'c4b5fd', 'accent_color' => 'a78bfa'],
                    'corporate' => ['bg_color' => 'f8fafc', 'heading_color' => '1e293b', 'description_color' => '475569', 'accent_color' => '2563eb'],
                ],
            ],
            'code' => [
                'name'           => 'Code Snippets',
                'icon'           => 'code',
                'description'    => 'Beautiful code snippet cards',
                'default_width'  => 1000,
                'default_height' => 600,
                'templates'      => [
                    'dark'        => ['name' => 'VS Code Dark', 'desc' => 'VS Code dark theme'],
                    'github_dark' => ['name' => 'GitHub Dark',  'desc' => 'GitHub dark theme'],
                    'dracula'     => ['name' => 'Dracula',       'desc' => 'Dracula color scheme'],
                    'minimal'     => ['name' => 'Minimal Light', 'desc' => 'Minimal light theme'],
                ],
                'defaults' => [
                    'dark'        => ['bg_color' => '1e1e1e', 'heading_color' => '9cdcfe', 'accent_color' => '569cd6', 'language' => 'javascript'],
                    'github_dark' => ['bg_color' => '0d1117', 'heading_color' => 'e6edf3', 'accent_color' => '79c0ff', 'language' => 'javascript'],
                    'dracula'     => ['bg_color' => '282a36', 'heading_color' => 'f8f8f2', 'accent_color' => 'bd93f9', 'language' => 'javascript'],
                    'minimal'     => ['bg_color' => 'fafafa', 'heading_color' => '383a42', 'accent_color' => '4078f2', 'language' => 'javascript'],
                ],
            ],
        'awan_tools' => [
            'name'           => 'Awan Tools',
            'icon'           => 'a',
            'description'    => 'Official Awan Tools branded OG images',
            'default_width'  => 1200,
            'default_height' => 630,
            'templates'      => [
                'homepage_og' => ['name' => 'Homepage OG', 'desc' => 'Live-count homepage Open Graph image'],
            ],
            'defaults' => [
                'homepage_og' => [
                    'bg_color'          => '62bcee',
                    'heading_color'     => '0a1628',
                    'description_color' => '0a1628',
                    'accent_color'      => '1565c0',
                    'heading'           => 'Awan Tools',
                    'footer'            => 'WWW.AWANTOOLS.SITE',
                ],
            ],
        ],
        ];
    }

    static function get_category(string $slug): ?array
    {
        $cats = self::categories();
        return $cats[$slug] ?? null;
    }

    static function get_template_defaults(string $cat, string $tpl): array
    {
        $cats = self::categories();
        return $cats[$cat]['defaults'][$tpl] ?? [];
    }

    static function exists(string $cat, string $tpl): bool
    {
        $cats = self::categories();
        return isset($cats[$cat]['templates'][$tpl]);
    }

    static function template_file(string $cat): string
    {
        return __DIR__ . '/templates/' . $cat . '.php';
    }
}
