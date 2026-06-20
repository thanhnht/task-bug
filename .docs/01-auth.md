# Đặc tả Use Case — Xác thực

---

## UC-01: Đăng nhập hệ thống

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-01 |
| **Tên** | Đăng nhập hệ thống |
| **Tác nhân chính** | Người dùng chưa đăng nhập |
| **Mức độ** | Người dùng |

### Mô tả
Người dùng cung cấp tên đăng nhập và mật khẩu để truy cập hệ thống. Hệ thống xác thực thông tin và cho phép hoặc từ chối truy cập.

### Tiền điều kiện
- Người dùng chưa đăng nhập vào hệ thống.
- Tài khoản đã được Admin tạo sẵn.

### Hậu điều kiện
- Thành công: Người dùng được xác thực, phiên làm việc được tạo. `last_login_at` được cập nhật. `login_attempts` được reset về 0.
- Thất bại: Người dùng ở lại trang đăng nhập với thông báo lỗi.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Truy cập `/login`, nhập `username` và `password` |
| 2 | Hệ thống | Tìm kiếm tài khoản theo `username` |
| 3 | Hệ thống | Kiểm tra tài khoản có đang bị khóa (`locked_until`) hay không |
| 4 | Hệ thống | Kiểm tra tài khoản có đang hoạt động (`is_active`) hay không |
| 5 | Hệ thống | Xác thực mật khẩu |
| 6 | Hệ thống | Cập nhật `last_login_at`, reset `login_attempts = 0` |
| 7 | Hệ thống | Kiểm tra `is_first_login` — nếu true, chuyển hướng sang UC-02 |
| 8 | Hệ thống | Chuyển hướng người dùng tới `/dashboard` |

### Luồng thay thế

**A1 — Tài khoản không tồn tại (Bước 2):**
> Hệ thống hiển thị thông báo "Tên đăng nhập hoặc mật khẩu không đúng." (không tiết lộ nguyên nhân cụ thể). Kết thúc use case.

**A2 — Tài khoản đang bị khóa (Bước 3):**
> Hệ thống hiển thị thông báo khóa và thời gian mở khóa tự động. Kết thúc use case.

**A3 — Tài khoản bị vô hiệu hóa (Bước 4):**
> Hệ thống hiển thị thông báo tài khoản bị vô hiệu hóa, liên hệ Admin. Kết thúc use case.

**A4 — Sai mật khẩu (Bước 5):**
> Hệ thống tăng `login_attempts` lên 1.
> - Nếu `login_attempts < 3`: hiển thị "Sai mật khẩu, còn N lần thử". Kết thúc use case.
> - Nếu `login_attempts = 3`: ghi `locked_until = now() + 30 phút`, hiển thị "Tài khoản bị khóa 30 phút". Kết thúc use case.

### Quy tắc nghiệp vụ
- Đăng nhập dựa trên `username`, không phải `email`.
- Sau 3 lần sai mật khẩu liên tiếp, tài khoản bị khóa tự động 30 phút.
- Tài khoản tự mở khóa sau khi hết thời hạn, hoặc Admin mở thủ công (UC-07).

---

## UC-02: Đổi mật khẩu lần đầu

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-02 |
| **Tên** | Đổi mật khẩu lần đầu |
| **Tác nhân chính** | Người dùng mới (is_first_login = true) |
| **Mức độ** | Người dùng |

### Mô tả
Người dùng đăng nhập lần đầu bằng mật khẩu tạm do Admin cấp bị bắt buộc đổi mật khẩu trước khi có thể truy cập bất kỳ chức năng nào khác của hệ thống.

### Tiền điều kiện
- Người dùng đã đăng nhập thành công.
- Trường `is_first_login = true` trên tài khoản.

### Hậu điều kiện
- Mật khẩu mới được lưu.
- `is_first_login` được cập nhật thành `false`.
- Người dùng được chuyển hướng tới `/dashboard`.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Hệ thống | Middleware `RequirePasswordChange` chặn mọi route, chuyển hướng về `/change-password` |
| 2 | Người dùng | Nhập mật khẩu hiện tại (tạm), mật khẩu mới, xác nhận mật khẩu mới |
| 3 | Hệ thống | Xác thực mật khẩu hiện tại đúng |
| 4 | Hệ thống | Kiểm tra độ mạnh mật khẩu mới |
| 5 | Hệ thống | Kiểm tra mật khẩu mới khác mật khẩu cũ |
| 6 | Hệ thống | Lưu mật khẩu mới, cập nhật `is_first_login = false` |
| 7 | Hệ thống | Chuyển hướng tới `/dashboard` |

### Luồng thay thế

**A1 — Mật khẩu hiện tại sai (Bước 3):**
> Hệ thống hiển thị lỗi "Mật khẩu hiện tại không đúng." Quay lại Bước 2.

**A2 — Mật khẩu mới không đủ mạnh (Bước 4):**
> Hệ thống hiển thị lỗi cụ thể (thiếu chữ hoa / số / ký tự đặc biệt / quá ngắn). Quay lại Bước 2.

**A3 — Mật khẩu mới trùng mật khẩu cũ (Bước 5):**
> Hệ thống hiển thị lỗi "Mật khẩu mới không được trùng mật khẩu cũ." Quay lại Bước 2.

### Quy tắc nghiệp vụ
- Mật khẩu mới yêu cầu: tối thiểu 8 ký tự, có ít nhất 1 chữ hoa, 1 chữ số, 1 ký tự đặc biệt trong tập `@$!%*#?&`.
- Trong khi `is_first_login = true`, mọi route đều bị chặn trừ `/change-password` và `/logout`.

---

## UC-03: Đăng xuất

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-03 |
| **Tên** | Đăng xuất khỏi hệ thống |
| **Tác nhân chính** | Người dùng đã đăng nhập |
| **Mức độ** | Người dùng |

### Mô tả
Người dùng kết thúc phiên làm việc, hệ thống hủy phiên và chuyển về trang đăng nhập.

### Tiền điều kiện
- Người dùng đang trong phiên làm việc hợp lệ.

### Hậu điều kiện
- Phiên làm việc bị hủy.
- Người dùng được chuyển về trang `/login`.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Nhấn nút "Đăng xuất" |
| 2 | Hệ thống | Gọi `POST /logout`, hủy phiên làm việc hiện tại |
| 3 | Hệ thống | Chuyển hướng về `/login` |

### Quy tắc nghiệp vụ
- Route `/logout` chỉ chấp nhận phương thức `POST` (bảo vệ CSRF).
