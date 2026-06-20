# Đặc tả Use Case — Hệ thống KPI

---

## UC-20: Xem điểm KPI và theo dõi hiệu suất

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-20 |
| **Tên** | Xem điểm KPI và theo dõi hiệu suất |
| **Tác nhân chính** | Mọi người dùng đã đăng nhập |
| **Mức độ** | Người dùng |

### Mô tả
Người dùng xem điểm KPI cá nhân trong tháng hiện tại và lịch sử các lần bị trừ điểm. PM và Admin được xem thêm bảng xếp hạng toàn đội để đánh giá hiệu suất chung.

### Tiền điều kiện
- Người dùng đã đăng nhập và hoàn tất đổi mật khẩu lần đầu.

### Hậu điều kiện
- Không thay đổi dữ liệu. Chỉ hiển thị.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Truy cập `/dashboard` |
| 2 | Hệ thống | Gọi `KpiService::scoreForMonth(userId, currentMonth)` tính điểm KPI tháng này |
| 3 | Hệ thống | Gọi `KpiService::transactionsForMonth(userId, currentMonth)` lấy danh sách giao dịch trừ điểm |
| 4 | Hệ thống | Hiển thị điểm tháng hiện tại và bảng lịch sử giao dịch (lý do, điểm trừ, thời gian) |
| 5 | Hệ thống | Nếu người dùng là PM hoặc Admin: gọi `KpiService::teamScores()` và hiển thị bảng xếp hạng toàn đội, sắp xếp tăng dần theo điểm |

### Quy tắc nghiệp vụ

**Công thức tính điểm:**
```
Điểm KPI = max(0, 100 + tổng(points))
           tính theo user_id và period_month (YYYY-MM)
```

**Các mức trừ điểm tự động:**

| Sự kiện | Mức trừ | Ai bị trừ | Trigger |
|---------|---------|----------|---------|
| Task hoàn thành trễ hạn | −2.0 / ngày trễ | Developer gốc | Task chuyển → `done` |
| Bug con được tạo | −0.25 / bug | Developer gốc của task cha | `storeChild()` tạo bug |
| RTT soak quá 48 giờ | −0.5 / ngày | Tester đang giữ task | Tester chuyển task ra khỏi RTT |
| Bug production được tạo | −5.0 | Developer gốc + Tester gốc | `store()` với `is_production_bug = true` |

**Xác định Developer gốc:**
> Người cuối cùng trong `task_histories` có hành động chuyển task sang `ready_to_test` (`changed_by`). Nếu không có lịch sử, fallback về `tasks.assigned_to`.

**Xác định Tester gốc:**
> Người cuối cùng trong `task_histories` có hành động chuyển task sang `review_approved` hoặc `done`.

**Phân quyền xem:**
- Developer / Tester: chỉ thấy điểm và lịch sử giao dịch của bản thân.
- PM / Admin: thấy thêm bảng xếp hạng KPI toàn đội dự án, sắp xếp từ điểm thấp nhất lên cao.
