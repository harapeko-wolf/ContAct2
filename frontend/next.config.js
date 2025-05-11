/** @type {import('next').NextConfig} */
const path = require('path');

const nextConfig = {
  // 静的エクスポートの設定を削除
  eslint: {
    ignoreDuringBuilds: true,
  },
  images: { unoptimized: true },
  
  // Webpackの設定
  webpack: (config) => {
    // canvasモジュールを外部依存として設定
    config.externals.push({
      canvas: 'canvas',
      'canvas-prebuilt': 'canvas-prebuilt'
    });
    
    // キャッシュの設定を無効化
    config.cache = false;
    
    return config;
  },
};

module.exports = nextConfig;