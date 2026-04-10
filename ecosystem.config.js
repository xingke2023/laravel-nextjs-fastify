module.exports = {
  apps: [
    // Laravel 由 Nginx + PHP-FPM 驱动，无需 PM2 管理
    {
      name: 'peking-fastify',
      script: 'src/main.js',
      cwd: '/home/ubuntu/peking/fastify-backend',
      env: {},
    },
    {
      name: 'peking-frontend',
      script: 'npm',
      args: 'start',
      cwd: '/home/ubuntu/peking/frontend',
      env: {},
    },
  ],
};
