const { test, expect } = require('@playwright/test');

const pages = [
    { path: '/login', heading: 'Access your WorkPulse workspace.' },
    { path: '/dashboard', heading: 'Today is Thursday, March 26' },
    { path: '/attendance', heading: 'Daily Attendance' },
    { path: '/leave', heading: 'Leave Summary' },
    { path: '/claims', heading: 'Claim Management' },
    { path: '/profile', heading: 'Your Profile' },
    { path: '/tasks', heading: 'Task Management' },
    { path: '/team', heading: 'Your Team' },
    { path: '/assets', heading: 'Assets Overview' },
    { path: '/report', heading: 'Reports' },
    { path: '/admin', heading: 'Admin Workspace' },
];

for (const pageConfig of pages) {
    test(`renders ${pageConfig.path}`, async ({ page }) => {
        await page.goto(pageConfig.path);
        await expect(page.getByText(pageConfig.heading, { exact: true })).toBeVisible();
    });
}

test('claims form print preview is renderable', async ({ page, browserName }, testInfo) => {
    test.skip(browserName !== 'chromium' || testInfo.project.name !== 'desktop', 'Print smoke test runs only once on desktop Chromium.');

    await page.goto('/claims');
    await page.locator('[data-claims-new]').click();
    await page.locator('[data-tab-trigger="form"]').click();
    await expect(page.locator('[data-tab-panel="form"]')).toBeVisible();

    await page.emulateMedia({ media: 'print' });

    const pdfPath = testInfo.outputPath('claims-print-preview.pdf');
    await page.pdf({
        path: pdfPath,
        format: 'A4',
        printBackground: true,
    });
});
