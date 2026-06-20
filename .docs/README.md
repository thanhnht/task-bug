# Đặc tả Yêu cầu Hệ thống — Task Bug Management

## 1. Giới thiệu hệ thống

**Tên hệ thống:** Task Bug Management System
**Nền tảng:** Laravel 10, PHP 8.1, MySQL, Blade Templates
**Mục tiêu:** Hệ thống quản lý dự án và theo dõi lỗi phần mềm nội bộ, hỗ trợ toàn bộ vòng đời phát triển từ khởi tạo task đến nghiệm thu, kết hợp đánh giá hiệu suất thành viên qua hệ thống KPI.

---

## 2. Tác nhân hệ thống

| Tác nhân | Ký hiệu | Mô tả |
|----------|---------|-------|
| Quản trị viên | Admin | Quản lý toàn bộ hệ thống: người dùng, dự án, có quyền cao nhất |
| Quản lý dự án | PM | Quản lý task, phân công, nghiệm thu; được Admin chỉ định trong từng dự án |
| Lập trình viên | Developer | Thực hiện công việc được giao, tạo child task, báo cáo tiến độ |
| Kiểm thử viên | Tester | Kiểm tra chất lượng, tạo bug, xác nhận kết quả test |
| Hệ thống | System | Thực hiện các hành động tự động: cascade trạng thái, gửi thông báo, tính KPI |

> Kế thừa quyền: Admin có toàn bộ quyền của PM. PM có toàn bộ quyền của thành viên dự án.

---

## 3. Danh sách Use Case

| Mã UC | Tên Use Case | Module | Tác nhân chính |
|-------|-------------|--------|---------------|
| UC-01 | Đăng nhập hệ thống | Xác thực | Mọi người dùng |
| UC-02 | Đổi mật khẩu lần đầu | Xác thực | Mọi người dùng |
| UC-03 | Đăng xuất | Xác thực | Mọi người dùng |
| UC-04 | Tạo tài khoản nhân viên | Quản trị | Admin |
| UC-05 | Reset mật khẩu nhân viên | Quản trị | Admin |
| UC-06 | Kích hoạt / Vô hiệu hóa tài khoản | Quản trị | Admin |
| UC-07 | Mở khóa tài khoản | Quản trị | Admin |
| UC-08 | Tạo dự án | Dự án | Admin |
| UC-09 | Xem danh sách & chi tiết dự án | Dự án | Mọi thành viên |
| UC-10 | Chỉnh sửa dự án | Dự án | Admin, PM |
| UC-11 | Quản lý thành viên dự án | Dự án | Admin, PM |
| UC-12 | Tạo task gốc | Task | Admin, PM |
| UC-13 | Tạo child task | Task | Mọi thành viên |
| UC-14 | Xem chi tiết task | Task | Mọi thành viên |
| UC-15 | Chỉnh sửa task | Task | Admin, PM, Creator |
| UC-16 | Chuyển trạng thái task | Task | Theo vai trò |
| UC-17 | Pass / Fail task (Tester) | Task | Tester, Admin |
| UC-18 | Báo lỗi từ QA (Child Bug) | Bug | Tester, Admin |
| UC-19 | Tạo Bug Production | Bug | Mọi thành viên |
| UC-20 | Xem điểm KPI | KPI | Mọi người dùng |
| UC-21 | Xem báo cáo chất lượng | Chất lượng | Mọi thành viên |
| UC-22 | Viết bình luận & đính kèm tệp | Cộng tác | Mọi thành viên |
| UC-23 | Quản lý thông báo | Thông báo | Mọi người dùng |

---

## 4. Tài liệu đặc tả chi tiết

| File | Nội dung |
|------|---------|
| [01-auth.md](01-auth.md) | UC-01, UC-02, UC-03 — Xác thực |
| [02-admin-users.md](02-admin-users.md) | UC-04, UC-05, UC-06, UC-07 — Quản trị người dùng |
| [03-project-management.md](03-project-management.md) | UC-08, UC-09, UC-10, UC-11 — Quản lý dự án |
| [04-task-workflow.md](04-task-workflow.md) | UC-12, UC-13, UC-14, UC-15, UC-16 — Quy trình task |
| [05-bug-tracking.md](05-bug-tracking.md) | UC-17, UC-18, UC-19 — Theo dõi bug |
| [06-kpi.md](06-kpi.md) | UC-20 — Hệ thống KPI |
| [07-quality-reports.md](07-quality-reports.md) | UC-21 — Báo cáo chất lượng |
| [08-comments.md](08-comments.md) | UC-22 — Bình luận & đính kèm |
| [09-notifications.md](09-notifications.md) | UC-23 — Thông báo |
