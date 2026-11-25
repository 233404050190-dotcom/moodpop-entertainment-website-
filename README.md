# MOODPOP – HƯỚNG DẪN CÀI ĐẶT VÀ SỬ DỤNG

## 1. Giới thiệu

MoodPop là website tin tức giải trí được xây dựng bằng PHP, HTML, CSS, JavaScript và MySQL/MariaDB. Hệ thống có hai nhóm người dùng chính: người đọc và quản trị viên.

**Chức năng người đọc:** xem danh sách và chi tiết bài viết; tìm kiếm và lọc theo chuyên mục; đăng ký, đăng nhập, đăng xuất; cập nhật hồ sơ; gửi bình luận.

**Chức năng quản trị viên:** xem Dashboard; thêm, chỉnh sửa, xóa, tìm kiếm và quản lý trạng thái bài viết; lựa chọn chuyên mục có sẵn; tải ảnh đại diện và chèn ảnh vào nội dung; kiểm duyệt bình luận.

**Các biện pháp bảo mật được sử dụng:** PDO Prepared Statements, băm và xác minh mật khẩu, Session, phân quyền, CSRF token, kiểm tra dữ liệu và kiểm soát số lần đăng nhập sai.

## 2. Công nghệ và môi trường

Môi trường phát triển/kiểm tra của dự án:

- Windows 11.
- XAMPP; MariaDB/MySQL được khởi động từ XAMPP.
- PHP 8.2.x, cần bật extension `pdo_mysql`.
- MariaDB 10.4.x hoặc MySQL tương thích.
- phpMyAdmin để quản lý và nhập cơ sở dữ liệu.
- Chrome hoặc trình duyệt hiện đại tương đương.

Website được chạy bằng PHP Development Server tại cổng `3000`. Apache chỉ cần bật nếu muốn truy cập phpMyAdmin thông qua XAMPP. Dự án không yêu cầu Composer và không cần chạy `composer install`.

## 3. Cấu trúc mã nguồn

Các thư mục và tệp được đặt trực tiếp tại thư mục gốc của gói `source_code.zip`:

```text
source_code/
├── README.md
├── index.php
├── news.php
├── post.php
├── register.php
├── login.php
├── logout.php
├── profile.php
├── comment_submit.php
├── test-db.php
├── moodpop_db_final.sql
├── config/
│   ├── database.php
│   ├── reader_auth.php
│   └── auth.php
├── admin/
│   ├── login.php
│   ├── logout.php
│   ├── dashboard.php
│   ├── posts.php
│   ├── post_form.php
│   ├── post_delete.php
│   ├── comments.php
│   └── upload_inline_image.php
└── assets/
    ├── css/
    ├── js/
    ├── image/
    └── uploads/posts/
```

## 4. Cài đặt cơ sở dữ liệu

### Bước 1. Khởi động MySQL/MariaDB

1. Mở XAMPP Control Panel.
2. Nhấn **Start** tại dịch vụ **MySQL**.
3. Nếu quản lý database bằng phpMyAdmin, khởi động thêm **Apache** rồi truy cập `http://localhost/phpmyadmin`.

### Bước 2. Tạo database

1. Trong phpMyAdmin, chọn **New**.
2. Tạo database có tên chính xác `moodpop_db`.
3. Chọn collation `utf8mb4_unicode_ci` nếu giao diện yêu cầu.

### Bước 3. Import dữ liệu

1. Chọn database `moodpop_db` vừa tạo.
2. Mở tab **Import**.
3. Chọn tệp `moodpop_db_final.sql` nằm ngay trong thư mục gốc mã nguồn.
4. Nhấn **Import/Go** và chờ thông báo nhập dữ liệu thành công.

Tệp SQL tạo và nạp dữ liệu cho 6 bảng: `users`, `categories`, `posts`, `comments`, `admin_login_attempts` và `reader_login_attempts`. Tệp SQL không tự tạo database; cần tạo `moodpop_db` trước khi import. Nên import vào database mới/trống để tránh dữ liệu trùng lặp.

## 5. Kiểm tra cấu hình kết nối

Mở tệp `config/database.php`. Cấu hình mặc định trong source:

```php
$host = '127.0.0.1';
$port = '3306';
$dbname = 'moodpop_db';
$username = 'root';
$password = '';
```

Nếu MySQL trên máy cài đặt dùng tài khoản hoặc mật khẩu khác, hãy sửa `$username` và `$password` cho khớp. Nếu thay đổi tên database hoặc cổng MySQL, cần cập nhật các biến tương ứng trong tệp này.

## 6. Chạy website

### Bước 1. Giải nén và đặt mã nguồn

1. Giải nén `source_code.zip`.
2. Sao chép **các thư mục và tệp nằm bên trong** thư mục `source_code` vào:

```text
C:\xampp\htdocs\moodpop
```

Sau khi sao chép, đường dẫn cần có dạng `C:\xampp\htdocs\moodpop\index.php` và `C:\xampp\htdocs\moodpop\config\database.php`.

### Bước 2. Khởi động PHP Development Server

Mở PowerShell hoặc Command Prompt và chạy:

```powershell
cd C:\xampp\htdocs\moodpop
C:\xampp\php\php.exe -S localhost:3000
```

Giữ cửa sổ terminal đang chạy trong khi sử dụng website. Để dừng máy chủ, nhấn `Ctrl + C`.

### Bước 3. Truy cập website

- Trang chủ: `http://localhost:3000/`
- Danh sách tin tức: `http://localhost:3000/news.php`
- Đăng ký: `http://localhost:3000/register.php`
- Đăng nhập người đọc: `http://localhost:3000/login.php`
- Đăng nhập quản trị: `http://localhost:3000/admin/login.php`
- Kiểm tra kết nối database: `http://localhost:3000/test-db.php`

## 7. Hướng dẫn sử dụng

### 7.1. Người đọc

1. Mở trang chủ để xem bài viết và chuyên mục.
2. Vào danh sách tin tức để tìm kiếm hoặc lọc bài viết.
3. Chọn bài viết để xem nội dung chi tiết.
4. Đăng ký tài khoản bằng email hợp lệ và mật khẩu đáp ứng điều kiện biểu mẫu.
5. Đăng nhập bằng tài khoản vừa đăng ký.
6. Cập nhật thông tin cá nhân tại trang hồ sơ.
7. Gửi bình luận tại bài viết. Bình luận được lưu ở trạng thái chờ và chỉ hiển thị công khai sau khi quản trị viên phê duyệt.

### 7.2. Quản trị viên

1. Truy cập `http://localhost:3000/admin/login.php`.
2. Đăng nhập bằng tài khoản có `role = 'admin'` trong bảng `users`.
3. Tại Dashboard, theo dõi các số liệu tổng quan.
4. Mở phần quản lý bài viết để thêm, sửa, xóa, tìm kiếm và thay đổi trạng thái bản nháp/đã xuất bản.
5. Khi tạo hoặc chỉnh sửa bài viết, chọn một chuyên mục đã có, nhập nội dung và tải ảnh theo biểu mẫu.
6. Mở phần bình luận để duyệt hoặc từ chối bình luận.

**Tài khoản Admin demo đã được kiểm tra đăng nhập trên máy phát triển và có trong tệp SQL đính kèm:**

| Trường | Giá trị |
|---|---|
| Email | `admin@moodpop.local` |
| Mật khẩu demo | `MoodPop@Demo2026!` |
| Vai trò | `admin` |

Sau khi import `moodpop_db_final.sql`, có thể đăng nhập bằng thông tin trên tại `http://localhost:3000/admin/login.php`. Database lưu mật khẩu dưới dạng hash, không lưu mật khẩu văn bản thuần. Tài khoản và mật khẩu này chỉ dành cho bài tập/môi trường demo cục bộ; không sử dụng cho tài khoản cá nhân hoặc hệ thống công khai.

Người đọc có thể tự đăng ký tài khoản mới từ giao diện website.

## 8. Kiểm tra nhanh sau cài đặt

- Mở `http://localhost:3000/test-db.php`; kiểm tra thông báo kết nối và danh sách chuyên mục.
- Kiểm tra trang chủ hiển thị các bài viết đã xuất bản.
- Đăng ký và đăng nhập một tài khoản người đọc.
- Mở bài viết, gửi bình luận và kiểm tra bình luận chờ duyệt trong khu vực Admin.
- Đăng nhập Admin; thử tạo bài viết bản nháp, chỉnh sửa và xuất bản.
- Kiểm tra tải ảnh đại diện và chèn ảnh trong trình soạn thảo.

## 9. Xử lý một số lỗi thường gặp

**Không kết nối được database**
- Kiểm tra MySQL/MariaDB đã chạy trong XAMPP.
- Kiểm tra đã tạo database `moodpop_db` và import SQL.
- Kiểm tra host, port, username, password trong `config/database.php`.
- Kiểm tra PHP đã bật extension `pdo_mysql`.

**Lỗi `Table doesn't exist`**
- Chọn đúng database `moodpop_db` trước khi import.
- Kiểm tra đủ 6 bảng: `users`, `categories`, `posts`, `comments`, `admin_login_attempts`, `reader_login_attempts`.

**Không tải được ảnh**
- Kiểm tra thư mục `assets/uploads/posts/` tồn tại và PHP có quyền ghi.
- Kiểm tra kích thước và định dạng ảnh được biểu mẫu chấp nhận.

**Không truy cập được cổng 3000**
- Kiểm tra terminal có thông báo PHP Development Server đã khởi động.
- Kiểm tra cổng 3000 không bị chương trình khác sử dụng.
- Giữ cửa sổ terminal đang chạy và tải lại trang.

## 10. Lưu ý khi nộp và triển khai

- Gói mã nguồn này phục vụ học tập và demo cục bộ, không phải cấu hình production.
- Cấu hình database mặc định dùng tài khoản `root` và mật khẩu rỗng theo môi trường phát triển. Khi triển khai thật cần tạo tài khoản database riêng, đặt mật khẩu mạnh, bật HTTPS và cấu hình máy chủ an toàn.
- Không công khai thông tin đăng nhập hoặc dữ liệu cá nhân của người dùng.
- `test-db.php` chỉ phục vụ kiểm tra cục bộ; nên gỡ bỏ hoặc hạn chế truy cập khi triển khai công khai.
- Database có bốn chuyên mục ban đầu: Phim ảnh, Âm nhạc, Showbiz và Truyền hình. Phiên bản hiện tại hỗ trợ chọn chuyên mục khi quản lý bài viết, chưa có trang CRUD chuyên mục độc lập.

---

**Tên dự án:** MoodPop – Website tin tức giải trí  
**Database:** `moodpop_db`  
**Tệp SQL:** `moodpop_db_final.sql`  
**Công nghệ:** PHP + PDO + MySQL/MariaDB
# moodpop-entertainment-website-
Personal project – Entertainment news website built with PHP and MySQL. 
