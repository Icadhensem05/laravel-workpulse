<x-layouts.app title="Profile - WorkPulse Laravel" page="profile">
    <section class="wp-panel p-6 sm:p-8">
        <div class="flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
            <div class="max-w-3xl">
                <p class="wp-label">Account</p>
                <h1 class="wp-page-title mt-3">Your Profile</h1>
                <p class="wp-section-copy mt-4 max-w-2xl">Update personal information, profile photo, and account security settings.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <x-ui.button variant="secondary">Edit Resume</x-ui.button>
                <x-ui.button data-profile-save>Save Changes</x-ui.button>
            </div>
        </div>
    </section>

    <section class="grid gap-5 xl:grid-cols-[1.15fr_0.85fr]">
        <section class="wp-panel p-6 sm:p-8">
            <div>
                <h2 class="wp-section-title">Basic Information</h2>
                <p class="wp-section-copy mt-2">What teammates see on your profile.</p>
            </div>

            <div class="mt-6 flex flex-col gap-6 lg:flex-row">
                <div class="flex min-w-44 flex-col items-center gap-4 text-center">
                    <div class="relative flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border border-white/10 bg-white/8 text-2xl font-semibold text-white" data-profile-avatar>
                        <img id="pfAvatarImg" alt="Profile photo" class="hidden h-full w-full object-cover">
                        <span id="pfAvatarFallback">MI</span>
                    </div>
                    <input id="pfPhoto" type="file" accept="image/*" class="hidden">
                    <x-ui.button variant="secondary" data-profile-upload-trigger aria-label="Upload profile photo">Upload Photo</x-ui.button>
                    <p class="wp-helper">PNG/JPG/WebP, max 2 MB</p>
                    <p class="wp-helper hidden" data-profile-upload-progress></p>
                </div>

                <div class="grid flex-1 gap-4 md:grid-cols-2">
                    <x-ui.input id="pfFirst" label="First Name" value="Muhammad" />
                    <x-ui.input id="pfLast" label="Last Name" value="Irsyad" />
                    <div class="md:col-span-2"><x-ui.input id="pfJob" label="Job Title" value="Internship" /></div>
                    <div class="md:col-span-2"><x-ui.input id="pfEmail" label="Email" value="irsyad050505@gmail.com" type="email" /></div>
                    <x-ui.input id="pfDept" label="Department" value="ICT" />
                    <x-ui.input id="pfBase" label="Base / Location" value="KLHQ" />
                    <div class="md:col-span-2"><x-ui.input id="pfPhone" label="Contact Number" value="0146630395" /></div>
                </div>
            </div>
        </section>

        <aside class="wp-panel p-6">
            <h2 class="wp-section-title">Security</h2>
            <p class="wp-section-copy mt-2">Update your password and account controls.</p>

            <div class="mt-6 space-y-4">
                <x-ui.input id="pfCurPass" label="Current Password" type="password" />
                <x-ui.input id="pfNewPass" label="New Password" type="password" />
                <x-ui.input id="pfNewPass2" label="Confirm New Password" type="password" />
                <x-ui.switch label="Enable session notifications" checked />
                <x-ui.button data-profile-password-save>Update Password</x-ui.button>
            </div>
        </aside>
    </section>
</x-layouts.app>
