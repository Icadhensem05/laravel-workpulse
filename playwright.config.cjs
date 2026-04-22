const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
    testDir: './tests/e2e',
    timeout: 30000,
    expect: {
        timeout: 5000,
    },
    use: {
        baseURL: 'http://127.0.0.1:8765',
        trace: 'off',
        screenshot: 'only-on-failure',
        video: 'off',
    },
    projects: [
        {
            name: 'desktop',
            use: {
                viewport: { width: 1440, height: 1024 },
            },
        },
        {
            name: 'tablet',
            use: {
                viewport: { width: 1180, height: 820 },
                isMobile: false,
                hasTouch: true,
            },
        },
        {
            name: 'mobile',
            use: {
                ...devices['Pixel 7'],
            },
        },
    ],
});
