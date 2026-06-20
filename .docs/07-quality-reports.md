# Đặc tả Use Case — Báo cáo Chất lượng

---

## UC-21: Xem báo cáo chất lượng

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-21 |
| **Tên** | Xem báo cáo chất lượng phần mềm |
| **Tác nhân chính** | Mọi thành viên dự án |
| **Mức độ** | Người dùng |

### Mô tả
Hệ thống cung cấp hai cấp độ báo cáo chất lượng: tổng quan toàn bộ dự án và chi tiết từng dự án. Báo cáo bao gồm các chỉ số đánh giá hiệu suất kiểm thử và lập trình, hỗ trợ lọc theo khoảng thời gian.

### Tiền điều kiện
- Người dùng đã đăng nhập và hoàn tất đổi mật khẩu lần đầu.

### Hậu điều kiện
- Không thay đổi dữ liệu. Chỉ hiển thị.

---

### UC-21a: Báo cáo tổng quan (`/quality`)

**Luồng chính:**

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Truy cập `/quality` |
| 2 | Người dùng | (Tùy chọn) Nhập khoảng thời gian lọc |
| 3 | Hệ thống | Tổng hợp chỉ số theo từng dự án người dùng có quyền xem |
| 4 | Hệ thống | Hiển thị bảng chỉ số: tổng bug, bug đang mở, bug đã đóng, DRE %, retest count |

**Chỉ số hiển thị:**

| Chỉ số | Công thức |
|--------|----------|
| DRE % | Bug đã đóng / Tổng bug × 100 |
| Retest count | Số lần task bị chuyển từ RTT về In Progress |
| Bug đang mở | Số bug chưa ở trạng thái `done` |

---

### UC-21b: Báo cáo chi tiết dự án (`/quality/{project}`)

**Luồng chính:**

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Chọn một dự án từ tổng quan hoặc truy cập trực tiếp `/quality/{project}` |
| 2 | Người dùng | (Tùy chọn) Nhập khoảng thời gian lọc |
| 3 | Hệ thống | Tính toán và hiển thị các nhóm chỉ số |

**Nhóm chỉ số tổng hợp:**
- Số task đã tạo / đã hoàn thành
- Tổng bug / bug mở / bug đóng / DRE %
- Retest rate = số lần retest / tổng task × 100

**Nhóm chỉ số theo Developer:**

| Chỉ số | Mô tả |
|--------|-------|
| Task hoàn thành | Số task gốc dev đã hoàn thành |
| Bug phát sinh | Số bug con được tạo từ task của dev |
| Retest count | Số lần task của dev bị test lại |
| Avg fix time | Thời gian trung bình từ khi bug tạo đến khi done (giờ) |
| Completion rate % | Task done / tổng task được giao × 100 |

**Nhóm chỉ số theo Tester:**

| Chỉ số | Mô tả |
|--------|-------|
| Bug tìm thấy | Số bug do tester này tạo ra |
| Bug đã đóng | Số bug tester xác nhận done |
| Task đã verify | Số task tester chuyển sang review_approved |
| DRE đóng góp % | % bug đóng do tester này so với tổng |

### Quy tắc nghiệp vụ — Phân quyền xem

| Vai trò | Phạm vi báo cáo |
|---------|----------------|
| Admin | Tất cả dự án, số liệu toàn bộ thành viên |
| PM | Dự án mình quản lý, số liệu toàn bộ thành viên |
| Developer | Dự án mình tham gia, **chỉ số liệu bản thân** |
| Tester | Dự án mình tham gia, **chỉ số liệu bản thân** |
