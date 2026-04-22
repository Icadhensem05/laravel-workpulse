import { fetchProfile, updatePassword, updateProfile, uploadProfilePhoto } from '../api/profile';
import { mapApiError } from '../api/client';
import { showGlobalFeedback } from '../ui/feedback';

export function initProfilePage() {
    const saveButton = document.querySelector('[data-profile-save]');
    const passwordButton = document.querySelector('[data-profile-password-save]');
    const uploadProgress = document.querySelector('[data-profile-upload-progress]');

    if (!saveButton && !passwordButton) {
        return;
    }

    const avatarImg = document.querySelector('#pfAvatarImg');
    const avatarFallback = document.querySelector('#pfAvatarFallback');

    function setUploadProgress(message = '') {
        if (!uploadProgress) {
            return;
        }

        uploadProgress.textContent = message;
        uploadProgress.classList.toggle('hidden', !message);
    }

    fetchProfile()
        .then((payload) => {
            const user = payload?.user;
            if (!user) return;

            const parts = String(user.name || '').trim().split(/\s+/);
            document.querySelector('#pfFirst') && (document.querySelector('#pfFirst').value = parts.shift() || '');
            document.querySelector('#pfLast') && (document.querySelector('#pfLast').value = parts.join(' '));
            document.querySelector('#pfJob') && (document.querySelector('#pfJob').value = user.job_title || '');
            document.querySelector('#pfEmail') && (document.querySelector('#pfEmail').value = user.email || '');
            document.querySelector('#pfDept') && (document.querySelector('#pfDept').value = user.department || '');
            document.querySelector('#pfBase') && (document.querySelector('#pfBase').value = user.base || '');
            document.querySelector('#pfPhone') && (document.querySelector('#pfPhone').value = user.phone || '');
            const initials = String(user.name || 'MI').trim().slice(0, 2).toUpperCase();
            if (avatarFallback) avatarFallback.textContent = initials;
            if (avatarImg && user.profile_photo) {
                avatarImg.src = user.profile_photo;
                avatarImg.classList.remove('hidden');
                avatarFallback?.classList.add('hidden');
            }
        })
        .catch((error) => {
            console.warn('profile api:', mapApiError(error));
            showGlobalFeedback(mapApiError(error), 'danger');
        });

    saveButton?.addEventListener('click', async () => {
        try {
            await updateProfile({
                first_name: document.querySelector('#pfFirst')?.value || '',
                last_name: document.querySelector('#pfLast')?.value || '',
                job_title: document.querySelector('#pfJob')?.value || '',
                email: document.querySelector('#pfEmail')?.value || '',
                department: document.querySelector('#pfDept')?.value || '',
                base: document.querySelector('#pfBase')?.value || '',
                phone: document.querySelector('#pfPhone')?.value || '',
            });
            showGlobalFeedback('Profile updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    passwordButton?.addEventListener('click', async () => {
        const next = document.querySelector('#pfNewPass')?.value || '';
        const confirm = document.querySelector('#pfNewPass2')?.value || '';

        if (next !== confirm) {
            showGlobalFeedback('New passwords do not match.', 'warning');
            return;
        }

        try {
            await updatePassword({
                current: document.querySelector('#pfCurPass')?.value || '',
                next,
            });
            showGlobalFeedback('Password updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        }
    });

    const uploadInput = document.querySelector('#pfPhoto');
    const uploadButton = document.querySelector('[data-profile-upload-trigger]');

    uploadButton?.addEventListener('click', () => uploadInput?.click());

    uploadInput?.addEventListener('change', async () => {
        const file = uploadInput.files?.[0];
        if (!file) return;

        try {
            setUploadProgress('Uploading photo... 0%');
            const payload = await uploadProfilePhoto(file, (progressEvent) => {
                const total = Number(progressEvent?.total || 0);
                const loaded = Number(progressEvent?.loaded || 0);
                const percent = total > 0 ? Math.min(100, Math.round((loaded / total) * 100)) : 0;
                setUploadProgress(`Uploading photo... ${percent}%`);
            });
            if (avatarImg && payload?.url) {
                avatarImg.src = payload.url;
                avatarImg.classList.remove('hidden');
                avatarFallback?.classList.add('hidden');
            }
            showGlobalFeedback('Profile photo updated successfully.', 'success');
        } catch (error) {
            showGlobalFeedback(mapApiError(error), 'danger');
        } finally {
            setUploadProgress('');
            uploadInput.value = '';
        }
    });
}
