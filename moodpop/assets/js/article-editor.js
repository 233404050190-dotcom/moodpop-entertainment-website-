document.addEventListener('DOMContentLoaded', () => {
    'use strict';

    // ====================================
    // LAY CAC THANH PHAN
    // ====================================

    const holder = document.getElementById(
        'inline-editor'
    );

    const content = document.getElementById(
        'content'
    );

    const picker = document.getElementById(
        'inline-image-picker'
    );

    const chooseButton = document.getElementById(
        'insert-inline-image'
    );

    const pasteButton = document.getElementById(
        'paste-inline-image'
    );

    const message = document.getElementById(
        'inline-image-status'
    );

    const form = document.querySelector(
        'form.post-form'
    );

    const saveButton = document.getElementById(
        'save-post'
    );

    const csrf = form?.querySelector(
        'input[name="csrf_token"]'
    );

    if (
        !holder ||
        !content ||
        !picker ||
        !chooseButton ||
        !message ||
        !form ||
        !saveButton ||
        !csrf
    ) {
        console.error(
            'MoodPop: Thiếu thành phần của trình soạn thảo.'
        );
        return;
    }

    // ====================================
    // AN O CHON FILE TRONG NOI DUNG
    // ====================================

    picker.hidden = true;

    picker.style.setProperty(
        'display',
        'none',
        'important'
    );

    // ====================================
    // CAU HINH
    // ====================================

    const MAX_IMAGES = 10;

    const MAX_BYTES = 2 * 1024 * 1024;

    const TYPES = [
        'image/png',
        'image/jpeg',
        'image/webp'
    ];

    const OPEN =
        String.fromCharCode(91, 91) + 'image:';

    const CLOSE =
        String.fromCharCode(93, 93);

    const validFilename =
        /^[a-f0-9]{32}[.](?:jpg|png|webp)$/;

    let activeText = null;

    let pending = 0;

    // ====================================
    // HIEN THI THONG BAO
    // ====================================

    function report(text, isError = false) {

        message.textContent = text;

        message.style.color = isError
            ? '#BE123C'
            : '#6D28D9';
    }

    function countImages() {

        return holder.querySelectorAll(
            '.mp-image, .mp-uploading'
        ).length;
    }

    // ====================================
    // TU DONG CHINH CHIEU CAO
    // ====================================

    function resize(input) {

        input.style.height = 'auto';

        input.style.height = Math.max(
            92,
            input.scrollHeight + 3
        ) + 'px';
    }

    // ====================================
    // TAO O NHAP VAN BAN
    // ====================================

    function makeText(value = '') {

        const textarea = document.createElement(
            'textarea'
        );

        textarea.className = 'mp-editor-text';

        textarea.rows = 3;

        textarea.value = value;

        textarea.placeholder =
            'Nhập chữ hoặc Ctrl + V để dán ảnh...';

        textarea.setAttribute(
            'aria-label',
            'Đoạn nội dung bài viết'
        );

        textarea.addEventListener('focus', () => {

            activeText = textarea;
        });

        textarea.addEventListener('click', () => {

            activeText = textarea;
        });

        textarea.addEventListener('input', () => {

            resize(textarea);
        });

        return textarea;
    }

    // ====================================
    // TAO KHOI ANH
    // ====================================

    function makeImage(filename) {

        const card = document.createElement('div');

        card.className = 'mp-image';

        card.dataset.filename = filename;

        const image = document.createElement('img');

        image.src =
            '../assets/uploads/posts/' + filename;

        image.alt = 'Ảnh trong nội dung bài viết';

        image.loading = 'lazy';

        // NUT XOA ANH

        const remove = document.createElement(
            'button'
        );

        remove.type = 'button';

        remove.className = 'mp-remove';

        remove.textContent = 'Xóa ảnh này';

        remove.addEventListener('click', () => {

            const nextInput = card.nextElementSibling;

            card.remove();

            if (
                nextInput?.matches(
                    'textarea.mp-editor-text'
                )
            ) {
                nextInput.focus();
            }

            report('Đã bỏ ảnh khỏi nội dung bài viết.');
        });

        card.append(image, remove);

        return card;
    }

    // ====================================
    // DOC NOI DUNG CU TU MYSQL
    // ====================================

    function loadSavedContent(value) {

        holder.replaceChildren();

        let consumed = 0;

        let searchFrom = 0;

        while (searchFrom < value.length) {

            const start = value.indexOf(
                OPEN,
                searchFrom
            );

            if (start < 0) {
                break;
            }

            const end = value.indexOf(
                CLOSE,
                start + OPEN.length
            );

            if (end < 0) {
                break;
            }

            const filename = value.slice(
                start + OPEN.length,
                end
            );

            // Neu marker sai, giu nguyen van ban

            if (!validFilename.test(filename)) {

                searchFrom = start + OPEN.length;
                continue;
            }

            // Doan van ban truoc anh

            holder.append(
                makeText(
                    value.slice(consumed, start)
                )
            );

            // Anh da luu

            holder.append(
                makeImage(filename)
            );

            consumed = end + CLOSE.length;

            searchFrom = consumed;
        }

        // Luon tao mot o nhap cuoi cung

        holder.append(
            makeText(
                value.slice(consumed)
            )
        );

        // Can chieu cao cac o van ban

        holder.querySelectorAll(
            'textarea.mp-editor-text'
        ).forEach(resize);

        activeText = lastText();
    }

    // ====================================
    // TIM O NHAP CUOI CUNG
    // ====================================

    function lastText() {

        const inputs = holder.querySelectorAll(
            'textarea.mp-editor-text'
        );

        if (inputs.length) {
            return inputs[inputs.length - 1];
        }

        const input = makeText();

        holder.append(input);

        return input;
    }

    function targetText() {

        if (activeText?.isConnected) {
            return activeText;
        }

        return lastText();
    }

    // ====================================
    // TAO O MOI SAU KHI CHEN ANH
    // ====================================

    function addUploadSlot(target) {

        const textarea = target?.isConnected
            ? target
            : lastText();

        const start =
            textarea.selectionStart ??
            textarea.value.length;

        const end =
            textarea.selectionEnd ??
            start;

        // CHIA DOAN VAN BAN THANH HAI PHAN

        const before = textarea.value.slice(
            0,
            start
        );

        const after = textarea.value.slice(end);

        textarea.value = before;

        resize(textarea);

        // VI TRI TAM CUA ANH

        const slot = document.createElement('div');

        slot.className = 'mp-uploading';

        slot.textContent = 'Đang tải ảnh...';

        // TAO O NHAP MOI NAM DUOI ANH

        const nextText = makeText(after);

        textarea.after(slot, nextText);

        resize(nextText);

        // QUAN TRONG:
        // TỰ ĐỘNG ĐƯA CON TRỎ SANG Ô MỚI

        activeText = nextText;

        nextText.focus();

        nextText.setSelectionRange(0, 0);

        return { slot, nextText };
    }

    // ====================================
    // TAI MOT ANH LEN WEBSITE
    // ====================================

    async function uploadOne(file, input) {

        // KIEM TRA DINH DANG

        if (
            !file ||
            !TYPES.includes(file.type)
        ) {
            report(
                'Chỉ hỗ trợ ảnh PNG, JPG hoặc WebP.',
                true
            );

            return false;
        }

        // KIEM TRA DUNG LUONG

        if (
            file.size < 1 ||
            file.size > MAX_BYTES
        ) {
            report(
                'Mỗi ảnh không được vượt quá 2 MB.',
                true
            );

            return false;
        }

        // KIEM TRA SO LUONG ANH

        if (countImages() >= MAX_IMAGES) {

            report(
                'Một bài viết chỉ được chèn tối đa 10 ảnh.',
                true
            );

            return false;
        }

        // Tao vi tri anh ngay lap tuc,
        // khong cho phan hoi tu server moi tao

        const { slot } = addUploadSlot(input);

        pending++;

        saveButton.disabled = true;

        report('Đang tải ảnh lên...');

        try {

            // GUI ANH BANG FORMDATA

            const data = new FormData();

            data.append(
                'csrf_token',
                csrf.value
            );

            data.append(
                'image',
                file,
                file.name || 'clipboard.png'
            );

            const response = await fetch(
                'upload_inline_image.php',
                {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: data
                }
            );

            const raw = await response.text();

            let json;

            try {

                json = JSON.parse(raw);

            } catch {

                throw new Error(
                    'Máy chủ không trả dữ liệu JSON. '
                    + 'Hãy kiểm tra đăng nhập Admin.'
                );
            }

            if (
                !response.ok ||
                json.ok !== true
            ) {
                throw new Error(
                    json.error ||
                    'Máy chủ không nhận ảnh.'
                );
            }

            // KIEM TRA TEN ANH TRA VE

            if (
                typeof json.filename !== 'string' ||
                !validFilename.test(json.filename)
            ) {
                throw new Error(
                    'Tên ảnh máy chủ trả về không hợp lệ.'
                );
            }

            // HIEN THI ANH DA UPLOAD

            slot.replaceWith(
                makeImage(json.filename)
            );

            report(
                'Đã chèn ' + countImages()
                + ' ảnh. Có thể tiếp tục Ctrl + V.'
            );

            return true;

        } catch (error) {

            slot.remove();

            report(
                'Không chèn được ảnh: '
                + (error?.message || 'Lỗi không xác định'),
                true
            );

            console.error(
                'MoodPop upload:',
                error
            );

            return false;

        } finally {

            pending--;

            saveButton.disabled = pending > 0;
        }
    }

    // ====================================
    // LAY CAC ANH TRONG CLIPBOARD
    // ====================================

    function imageFilesFromClipboard(clipboard) {

        const items = Array.from(
            clipboard?.items || []
        );

        const images = items
            .filter(item =>
                item.kind === 'file' &&
                item.type.startsWith('image/')
            )
            .map(item => item.getAsFile())
            .filter(Boolean);

        if (images.length) {
            return images;
        }

        return Array.from(
            clipboard?.files || []
        ).filter(file =>
            file.type.startsWith('image/')
        );
    }

    // ====================================
    // DOC ANH DATA URL TU HTML CLIPBOARD
    // ====================================

    async function imageFromHtml(clipboard) {

        const html = clipboard?.getData(
            'text/html'
        ) || '';

        if (!html || !/<img\b/i.test(html)) {
            return null;
        }

        const parsed = new DOMParser()
            .parseFromString(html, 'text/html');

        const src = parsed.querySelector('img')
            ?.getAttribute('src') || '';

        // Chi doc du lieu anh nhung truc tiep
        // Khong tu tai anh tu website ben ngoai

        if (
            !/^data:image\/(?:png|jpeg|webp);base64,/i
                .test(src)
        ) {
            return null;
        }

        const response = await fetch(src);

        const blob = await response.blob();

        const mime = blob.type === 'image/jpg'
            ? 'image/jpeg'
            : blob.type;

        const extension = mime === 'image/jpeg'
            ? 'jpg'
            : mime.split('/')[1];

        return new File(
            [blob],
            'clipboard.' + extension,
            { type: mime }
        );
    }

    // ====================================
    // CHEN NHIEU ANH LIEN TIEP
    // ====================================

    function pasteFiles(files, initialInput) {

        let nextTarget =
            initialInput || targetText();

        for (const file of files) {

            // uploadOne tao o nhap moi
            // TRUOC khi doi server phan hoi

            void uploadOne(file, nextTarget);

            // Lay o moi de chen anh tiep theo

            nextTarget = targetText();
        }
    }

    // ====================================
    // CTRL + V TRONG VUNG SOAN THAO
    // ====================================

    // Dung event delegation:
    // cac textarea duoc tao moi van nhan paste

    holder.addEventListener(
        'paste',
        async event => {

            const target =
                event.target.closest(
                    'textarea.mp-editor-text'
                ) || targetText();

            const clipboard = event.clipboardData;

            const images =
                imageFilesFromClipboard(clipboard);

            // NEU COPY ANH

            if (images.length) {

                event.preventDefault();

                pasteFiles(images, target);

                return;
            }

            // NEU CLIPBOARD CHUA HTML ANH

            const html =
                clipboard?.getData('text/html') || '';

            if (/<img\b/i.test(html)) {

                event.preventDefault();

                try {

                    const image = await imageFromHtml(
                        clipboard
                    );

                    if (image) {

                        pasteFiles([image], target);

                    } else {

                        report(
                            'Nguồn này chỉ sao chép liên kết ảnh. '
                            + 'Hãy chọn Sao chép hình ảnh '
                            + 'hoặc dùng Win + Shift + S.',
                            true
                        );
                    }

                } catch (error) {

                    report(
                        'Không đọc được ảnh: '
                        + error.message,
                        true
                    );
                }

                return;
            }

            // NEU COPY CHU:
            // textarea se dan van ban binh thuong
        },
        true
    );

    // ====================================
    // NEU FOCUS ROI RA NGOAI EDITOR
    // ====================================

    document.addEventListener(
        'paste',
        event => {

            if (holder.contains(event.target)) {
                return;
            }

            if (!activeText?.isConnected) {
                return;
            }

            // Khong can thiep khi nguoi dung
            // dang dan vao tieu de, mo ta, slug...

            const focused = document.activeElement;

            if (
                focused &&
                form.contains(focused)
            ) {
                return;
            }

            const images = imageFilesFromClipboard(
                event.clipboardData
            );

            if (!images.length) {
                return;
            }

            event.preventDefault();

            pasteFiles(images, targetText());
        },
        true
    );

    // ====================================
    // CHON ANH TU MAY TINH
    // ====================================

    chooseButton.addEventListener(
        'click',
        () => {

            picker.click();
        }
    );

    picker.addEventListener('change', () => {

        const files = Array.from(
            picker.files || []
        );

        picker.value = '';

        if (files.length) {

            pasteFiles(
                files,
                targetText()
            );
        }
    });

    // ====================================
    // NUT DAN ANH TU CLIPBOARD
    // ====================================

    if (pasteButton) {

        pasteButton.addEventListener(
            'click',
            async () => {

                try {

                    if (!navigator.clipboard?.read) {

                        throw new Error(
                            'Trình duyệt không hỗ trợ '
                            + 'đọc clipboard trực tiếp.'
                        );
                    }

                    const entries =
                        await navigator.clipboard.read();

                    const files = [];

                    for (const entry of entries) {

                        const mime = TYPES.find(
                            type =>
                                entry.types.includes(type)
                        );

                        if (!mime) {
                            continue;
                        }

                        const blob =
                            await entry.getType(mime);

                        const ext =
                            mime === 'image/jpeg'
                                ? 'jpg'
                                : mime.split('/')[1];

                        files.push(
                            new File(
                                [blob],
                                'clipboard.' + ext,
                                { type: mime }
                            )
                        );
                    }

                    if (!files.length) {

                        report(
                            'Clipboard chưa chứa ảnh JPG, '
                            + 'PNG hoặc WebP.',
                            true
                        );

                        return;
                    }

                    pasteFiles(
                        files,
                        targetText()
                    );

                } catch (error) {

                    report(
                        'Không đọc được clipboard: '
                        + error.message
                        + '. Hãy thử Ctrl + V '
                        + 'trong ô nội dung.',
                        true
                    );
                }
            }
        );
    }

    // ====================================
    // CHUYEN NOI DUNG DE LUU MYSQL
    // ====================================

    function serialize() {

        const blocks = [];

        for (const element of holder.children) {

            // DOAN VAN BAN

            if (
                element.matches(
                    'textarea.mp-editor-text'
                )
            ) {
                blocks.push(element.value);

            // HINH ANH

            } else if (
                element.classList.contains(
                    'mp-image'
                )
            ) {
                blocks.push(
                    OPEN +
                    element.dataset.filename +
                    CLOSE
                );
            }
        }

        return blocks
            .join('\n')
            .replace(/\n{3,}/g, '\n\n')
            .trim();
    }

    // ====================================
    // LUU BAI VIET
    // ====================================

    form.addEventListener('submit', event => {

        if (pending > 0) {

            event.preventDefault();

            report(
                'Ảnh đang tải, chưa thể lưu bài viết.',
                true
            );

            return;
        }

        const text = Array.from(
            holder.querySelectorAll(
                'textarea.mp-editor-text'
            )
        )
            .map(input => input.value)
            .join('\n')
            .trim();

        const length = Array.from(
            text
        ).length;

        if (
            length < 30 ||
            length > 100000
        ) {
            event.preventDefault();

            report(
                'Nội dung chữ phải từ '
                + '30 đến 100000 ký tự.',
                true
            );

            return;
        }

        // Chuyen du lieu editor vao textarea
        // de gui ve post_form.php

        content.value = serialize();
    });

    // ====================================
    // KHOI TAO EDITOR
    // ====================================

    loadSavedContent(content.value);

    holder.style.display = 'flex';

    content.style.display = 'none';

    // KHONG HIEN "SAN SANG: 0/10 ANH"

    report('');

});
