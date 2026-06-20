# Đặc tả Use Case — Quản trị Người dùng

---

## UC-04: Tạo tài khoản nhân viên

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-04 |
| **Tên** | Tạo tài khoản nhân viên |
| **Tác nhân chính** | Admin |
| **Mức độ** | Quản trị |

### Mô tả
Admin tạo tài khoản mới cho nhân viên. Hệ thống tự động sinh tên đăng nhập và mật khẩu tạm, sau đó gửi email thông báo cho nhân viên.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin.

### Hậu điều kiện
- Tài khoản nhân viên mới được tạo trong hệ thống với `is_first_login = true`.
- Email chào mừng kèm thông tin đăng nhập được gửi tới email nhân viên.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin | Truy cập `/admin/users/create`, điền thông tin: họ tên, email, số điện thoại (tùy chọn) |
| 2 | Hệ thống | Xác thực dữ liệu đầu vào (họ tên bắt buộc, email hợp lệ và chưa tồn tại) |
| 3 | Hệ thống | Tự động sinh `username` theo quy tắc: họ + chữ cái đầu các tên đệm + tên (ví dụ: `nguyenvana`) |
| 4 | Hệ thống | Tự động sinh mật khẩu tạm ngẫu nhiên đủ mạnh |
| 5 | Hệ thống | Tạo tài khoản với `is_first_login = true`, `is_active = true` |
| 6 | Hệ thống | Gửi email chào mừng gồm username và mật khẩu tạm |
| 7 | Hệ thống | Chuyển hướng về danh sách người dùng với thông báo thành công |

### Luồng thay thế

**A1 — Email đã tồn tại (Bước 2):**
> Hệ thống hiển thị lỗi "Email này đã được sử dụng." Quay lại Bước 1.

**A2 — Gửi email thất bại (Bước 6):**
> Hệ thống vẫn tạo tài khoản thành công nhưng hiển thị cảnh báo "Không gửi được email. Admin cần thông báo thủ công."

### Quy tắc nghiệp vụ
- Chỉ Admin mới có quyền tạo tài khoản.
- Username được sinh tự động, nhân viên không tự chọn được.
- Nhân viên mới bắt buộc đổi mật khẩu khi đăng nhập lần đầu (UC-02).

---

## UC-05: Reset mật khẩu nhân viên

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-05 |
| **Tên** | Reset mật khẩu nhân viên |
| **Tác nhân chính** | Admin |
| **Mức độ** | Quản trị |

### Mô tả
Khi nhân viên quên mật khẩu hoặc cần cấp lại quyền truy cập, Admin reset mật khẩu. Hệ thống sinh mật khẩu tạm mới và gửi email thông báo.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin.
- Tài khoản nhân viên cần reset tồn tại trong hệ thống.

### Hậu điều kiện
- Mật khẩu mới được ghi vào tài khoản nhân viên.
- `is_first_login` được đặt lại thành `true`.
- Email kèm mật khẩu mới được gửi tới nhân viên.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin | Truy cập danh sách người dùng, chọn "Reset mật khẩu" cho nhân viên cần thiết |
| 2 | Hệ thống | Sinh mật khẩu tạm ngẫu nhiên mới |
| 3 | Hệ thống | Cập nhật mật khẩu, đặt `is_first_login = true` |
| 4 | Hệ thống | Gửi email thông báo mật khẩu mới cho nhân viên |
| 5 | Hệ thống | Hiển thị thông báo "Reset mật khẩu thành công" |

### Quy tắc nghiệp vụ
- Sau khi reset, nhân viên bắt buộc đổi mật khẩu khi đăng nhập lần tiếp theo (UC-02).

---

## UC-06: Kích hoạt / Vô hiệu hóa tài khoản

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-06 |
| **Tên** | Kích hoạt / Vô hiệu hóa tài khoản |
| **Tác nhân chính** | Admin |
| **Mức độ** | Quản trị |

### Mô tả
Admin bật hoặc tắt quyền truy cập của một tài khoản nhân viên mà không xóa tài khoản đó khỏi hệ thống.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin.
- Tài khoản cần thay đổi tồn tại và không phải tài khoản Admin.

### Hậu điều kiện
- Trường `is_active` của tài khoản được cập nhật.
- Nếu nhân viên đang đăng nhập khi bị vô hiệu hóa: middleware `EnsureAccountActive` tự động đăng xuất họ ở lần request tiếp theo.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin | Nhấn nút toggle trạng thái tài khoản trên danh sách người dùng |
| 2 | Hệ thống | Kiểm tra tài khoản mục tiêu không phải Admin |
| 3 | Hệ thống | Đảo giá trị `is_active` (true → false hoặc ngược lại) |
| 4 | Hệ thống | Hiển thị thông báo kết quả |

### Luồng thay thế

**A1 — Cố vô hiệu hóa tài khoản Admin (Bước 2):**
> Hệ thống từ chối, hiển thị lỗi "Không thể vô hiệu hóa tài khoản Admin." Kết thúc use case.

---

## UC-07: Mở khóa tài khoản

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-07 |
| **Tên** | Mở khóa tài khoản bị khóa |
| **Tác nhân chính** | Admin |
| **Mức độ** | Quản trị |

### Mô tả
Tài khoản bị khóa sau 3 lần nhập sai mật khẩu. Admin có thể mở khóa thủ công trước khi hết 30 phút tự động.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin.
- Tài khoản mục tiêu đang trong trạng thái bị khóa (`locked_until` chưa hết hạn).

### Hậu điều kiện
- `locked_until` được đặt về `null`.
- `login_attempts` được đặt về `0`.
- Nhân viên có thể đăng nhập lại ngay lập tức.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin | Truy cập danh sách người dùng, nhận biết tài khoản đang bị khóa qua badge trạng thái |
| 2 | Admin | Nhấn nút "Mở khóa" trên tài khoản tương ứng |
| 3 | Hệ thống | Cập nhật `locked_until = null`, `login_attempts = 0` |
| 4 | Hệ thống | Hiển thị thông báo "Đã mở khóa tài khoản thành công" |

### Quy tắc nghiệp vụ
- Tài khoản tự động mở khóa sau 30 phút kể từ lần sai mật khẩu thứ 3. Admin mở khóa sớm hơn khi cần thiết.
