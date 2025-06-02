/** @type {import('next').NextConfig} */
const path = require('path');

const nextConfig = {
  // 静的エクスポートの設定を削除
  eslint: {
    ignoreDuringBuilds: true,
  },
  images: { unoptimized: true },
  
  // Webpackの設定
  webpack: (config, { isServer }) => {
    // canvasモジュールを外部依存として設定
    config.externals.push({
      canvas: 'canvas',
      'canvas-prebuilt': 'canvas-prebuilt'
    });
    
    // Node.js polyfillsの設定
    if (!isServer) {
      config.resolve.fallback = {
        ...config.resolve.fallback,
        fs: false,
        net: false,
        tls: false,
        crypto: false,
        path: false,
        stream: false,
        util: false,
        zlib: false,
        http: false,
        https: false,
        url: false,
        buffer: false,
        child_process: false,
      };
    }
    
    // experimental機能の設定
    config.experiments = {
      ...config.experiments,
      topLevelAwait: true,
    };
    
    return config;
  },
  
  // 実験的機能
  experimental: {
    serverActions: {
      allowedOrigins: ['localhost:3000'],
    },
  },
  
  // CORS関連のヘッダー設定
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          {
            key: 'Cross-Origin-Embedder-Policy',
            value: 'unsafe-none',
          },
          {
            key: 'Cross-Origin-Opener-Policy',
            value: 'same-origin-allow-popups',
          },
        ],
      },
    ];
  },
};

module.exports = nextConfig;