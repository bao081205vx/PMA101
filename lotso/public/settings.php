<?php
require_once '../templates/header.php';
require_once '../templates/sidebar.php';
?>

<div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Cài đặt hệ thống</h2>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Thông tin cửa hàng</h5>
                </div>
                <div class="card-body">
                    <form id="storeSettingsForm">
                        <div class="mb-3">
                            <label class="form-label">Tên cửa hàng</label>
                            <input type="text" class="form-control" id="storeName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Địa chỉ</label>
                            <input type="text" class="form-control" id="storeAddress" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Số điện thoại</label>
                            <input type="tel" class="form-control" id="storePhone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" id="storeEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Logo</label>
                            <input type="file" class="form-control" id="storeLogo" accept="image/*">
                            <div id="currentLogo" class="mt-2"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Cài đặt SEO</h5>
                </div>
                <div class="card-body">
                    <form id="seoSettingsForm">
                        <div class="mb-3">
                            <label class="form-label">Meta Title</label>
                            <input type="text" class="form-control" id="metaTitle">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Description</label>
                            <textarea class="form-control" id="metaDescription" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Meta Keywords</label>
                            <input type="text" class="form-control" id="metaKeywords">
                            <small class="text-muted">Phân cách các từ khóa bằng dấu phẩy</small>
                        </div>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mạng xã hội</h5>
                </div>
                <div class="card-body">
                    <form id="socialSettingsForm">
                        <div class="mb-3">
                            <label class="form-label">Facebook</label>
                            <input type="url" class="form-control" id="facebook">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Instagram</label>
                            <input type="url" class="form-control" id="instagram">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Youtube</label>
                            <input type="url" class="form-control" id="youtube">
                        </div>
                        <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Hàm tải cài đặt
async function loadSettings() {
    try {
        const response = await fetch('/lotso/api/settings');
        const result = await response.json();
        if (result.success) {
            const settings = result.data;
            
            // Cài đặt cửa hàng
            document.getElementById('storeName').value = settings.store_name || '';
            document.getElementById('storeAddress').value = settings.store_address || '';
            document.getElementById('storePhone').value = settings.store_phone || '';
            document.getElementById('storeEmail').value = settings.store_email || '';
            if (settings.store_logo) {
                document.getElementById('currentLogo').innerHTML = `
                    <img src="${settings.store_logo}" alt="Logo" class="img-thumbnail" style="max-height: 100px">
                `;
            }

            // Cài đặt SEO
            document.getElementById('metaTitle').value = settings.meta_title || '';
            document.getElementById('metaDescription').value = settings.meta_description || '';
            document.getElementById('metaKeywords').value = settings.meta_keywords || '';

            // Cài đặt mạng xã hội
            document.getElementById('facebook').value = settings.facebook || '';
            document.getElementById('instagram').value = settings.instagram || '';
            document.getElementById('youtube').value = settings.youtube || '';
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi tải cài đặt');
    }
}

// Hàm lưu cài đặt cửa hàng
async function saveStoreSettings(event) {
    event.preventDefault();
    try {
        const formData = new FormData();
        formData.append('store_name', document.getElementById('storeName').value);
        formData.append('store_address', document.getElementById('storeAddress').value);
        formData.append('store_phone', document.getElementById('storePhone').value);
        formData.append('store_email', document.getElementById('storeEmail').value);
        
        const logoFile = document.getElementById('storeLogo').files[0];
        if (logoFile) {
            formData.append('store_logo', logoFile);
        }

        const response = await fetch('/lotso/api/settings/store', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        if (result.success) {
            alert('Đã lưu thay đổi thành công');
            loadSettings();
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lưu cài đặt');
    }
}

// Hàm lưu cài đặt SEO
async function saveSeoSettings(event) {
    event.preventDefault();
    try {
        const response = await fetch('/lotso/api/settings/seo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                meta_title: document.getElementById('metaTitle').value,
                meta_description: document.getElementById('metaDescription').value,
                meta_keywords: document.getElementById('metaKeywords').value
            })
        });
        const result = await response.json();
        if (result.success) {
            alert('Đã lưu thay đổi thành công');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lưu cài đặt');
    }
}

// Hàm lưu cài đặt mạng xã hội
async function saveSocialSettings(event) {
    event.preventDefault();
    try {
        const response = await fetch('/lotso/api/settings/social', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                facebook: document.getElementById('facebook').value,
                instagram: document.getElementById('instagram').value,
                youtube: document.getElementById('youtube').value
            })
        });
        const result = await response.json();
        if (result.success) {
            alert('Đã lưu thay đổi thành công');
        } else {
            alert('Lỗi: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi lưu cài đặt');
    }
}

// Thêm event listener
document.addEventListener('DOMContentLoaded', () => {
    loadSettings();
    document.getElementById('storeSettingsForm').addEventListener('submit', saveStoreSettings);
    document.getElementById('seoSettingsForm').addEventListener('submit', saveSeoSettings);
    document.getElementById('socialSettingsForm').addEventListener('submit', saveSocialSettings);
});
</script>

<?php require_once '../templates/footer.php'; ?>
