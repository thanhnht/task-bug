# Đặc tả Use Case — Thông báo

---

## UC-23: Quản lý thông báo

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-23 |
| **Tên** | Xem và quản lý thông báo hệ thống |
| **Tác nhân chính** | Mọi người dùng đã đăng nhập |
| **Tác nhân phụ** | Hệ thống (gửi thông báo tự động) |
| **Mức độ** | Người dùng |

### Mô tả
Hệ thống tự động gửi thông báo nội bộ khi xảy ra các sự kiện liên quan đến task. Người dùng xem danh sách thông báo, nhấn vào từng thông báo để điều hướng đến task tương ứng, và đánh dấu đã đọc.

### Tiền điều kiện
- Người dùng đã đăng nhập và hoàn tất đổi mật khẩu lần đầu.

### Hậu điều kiện
- Thông báo được đánh dấu đã đọc (`read_at` được ghi nhận).

---

### Luồng phụ — Hệ thống gửi thông báo (tự động)

Hệ thống gửi thông báo khi xảy ra các sự kiện sau:

| Sự kiện | Loại thông báo | Gửi cho ai |
|---------|--------------|-----------|
| Task được gán cho người dùng | `assigned` | Người được gán |
| Bug chuyển sang `ready_to_test` | `bug_ready_to_test` | Tester trong dự án (ưu tiên tester được gán, nếu không có thì tất cả tester) |
| Task gốc chuyển sang `review_approved` | `review_approved` | Tất cả PM trong dự án |

---

### Luồng chính — Xem thông báo

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Truy cập `/notifications` |
| 2 | Hệ thống | Lấy danh sách thông báo của người dùng, sắp xếp theo thời gian mới nhất, phân trang 30 mục/trang |
| 3 | Hệ thống | Tự động đánh dấu toàn bộ thông báo là đã đọc khi người dùng vào trang |
| 4 | Hệ thống | Hiển thị từng thông báo: tiêu đề, nội dung, thời gian, trạng thái đọc/chưa đọc |

---

### Luồng chính — Mở thông báo và điều hướng

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Nhấn vào một thông báo cụ thể |
| 2 | Hệ thống | Đánh dấu thông báo đó là đã đọc (`read_at = now()`) |
| 3 | Hệ thống | Chuyển hướng người dùng tới URL task liên quan (trường `url` trong `user_notifications`) |

---

### Luồng chính — Đánh dấu tất cả đã đọc

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Nhấn "Đọc tất cả" |
| 2 | Hệ thống | Cập nhật `read_at = now()` cho tất cả thông báo chưa đọc của người dùng |
| 3 | Hệ thống | Làm mới danh sách, không còn hiển thị badge số chưa đọc |

---

### Quy tắc nghiệp vụ
- Số thông báo chưa đọc hiển thị trên thanh điều hướng (sidebar/header) dưới dạng badge.
- Phân trang: 30 thông báo mỗi trang.
- Thông báo không bị xóa; chỉ thay đổi trạng thái đọc/chưa đọc.

### Cấu trúc bảng `user_notifications`

| Cột | Kiểu | Ý nghĩa |
|-----|------|---------|
| `user_id` | FK | Người nhận thông báo |
| `task_id` | FK | Task liên quan |
| `type` | string | `assigned` / `bug_ready_to_test` / `review_approved` |
| `title` | string | Tiêu đề ngắn gọn |
| `body` | string | Nội dung chi tiết |
| `url` | string | Đường dẫn tới task |
| `read_at` | timestamp | `null` = chưa đọc; có giá trị = đã đọc |
