# Sơ đồ Use Case

Tất cả file `.puml` dùng cú pháp [PlantUML](https://plantuml.com).

## Cách xem

**VS Code:** Cài extension [PlantUML](https://marketplace.visualstudio.com/items?itemName=jebbs.plantuml), mở file `.puml` → nhấn `Alt+D` để preview.

**Online:** Copy nội dung vào [plantuml.com/plantuml/uml](http://www.plantuml.com/plantuml/uml)

---

## Danh sách sơ đồ

| File | Tiêu đề | Mô tả |
|------|---------|-------|
| [00-overview.puml](00-overview.puml) | Tổng quan hệ thống | Tất cả actor và use case chính, phân theo module |
| [01-auth.puml](01-auth.puml) | Xác thực & Tài khoản | Đăng nhập, đổi mật khẩu lần đầu, lockout |
| [02-admin-users.puml](02-admin-users.puml) | Quản trị Người dùng | Tạo tài khoản, reset pass, khóa/mở khóa |
| [03-project-management.puml](03-project-management.puml) | Quản lý Dự án | Tạo dự án, thành viên, vai trò |
| [04-task-workflow.puml](04-task-workflow.puml) | Quy trình Task | Tạo task, chuyển trạng thái, auto-cascade |
| [05-bug-tracking.puml](05-bug-tracking.puml) | Theo dõi Bug | Bug QA (child), Bug Production (root), KPI deduction |
| [06-kpi.puml](06-kpi.puml) | Hệ thống KPI | Các mức trừ điểm, trigger, xem báo cáo |
| [07-quality-reports.puml](07-quality-reports.puml) | Báo cáo Chất lượng | DRE, retest rate, thống kê dev/tester |
| [08-comments-notifications.puml](08-comments-notifications.puml) | Bình luận & Thông báo | Comment, file đính kèm, push notification |
