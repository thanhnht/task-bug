# Đặc tả Use Case — Quy trình Task

---

## UC-12: Tạo task gốc

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-12 |
| **Tên** | Tạo task gốc (root task) |
| **Tác nhân chính** | Admin, PM |
| **Mức độ** | Người dùng |

### Mô tả
Admin hoặc PM tạo một task gốc (không có task cha) đại diện cho một hạng mục công việc trong dự án. Task gốc là đơn vị quản lý chính, có thể được chia nhỏ thành các child task.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin hoặc PM trong dự án.
- Dự án đang ở trạng thái `active`.

### Hậu điều kiện
- Task mới được tạo với `parent_id = null`, mã tự sinh (`TSK-NNN`), trạng thái `todo`.
- Thông báo được gửi tới người được phân công (nếu có).

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin/PM | Truy cập `/projects/{project}/tasks/create`, điền thông tin: tiêu đề (bắt buộc), loại, mô tả, mức ưu tiên, ngày bắt đầu, ngày kết thúc, giờ ước tính, người nhận |
| 2 | Hệ thống | Xác thực dữ liệu |
| 3 | Hệ thống | Tự sinh mã task (`TSK-NNN`), tạo bản ghi với `parent_id = null`, `status = todo` |
| 4 | Hệ thống | Gửi thông báo "assigned" tới người được phân công (nếu có) |
| 5 | Hệ thống | Chuyển hướng tới trang chi tiết task vừa tạo |

### Quy tắc nghiệp vụ
- Chỉ Admin và PM tạo được task gốc loại `task`.
- Bất kỳ thành viên nào cũng có thể tạo task gốc loại `bug` (xem UC-19).

---

## UC-13: Tạo child task

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-13 |
| **Tên** | Tạo child task |
| **Tác nhân chính** | Mọi thành viên dự án |
| **Mức độ** | Người dùng |

### Mô tả
Thành viên dự án tạo một task con dưới task cha để chia nhỏ công việc hoặc ghi nhận subtask, fix, research, test.

### Tiền điều kiện
- Người dùng là thành viên của dự án.
- Task cha tồn tại trong hệ thống.

### Hậu điều kiện
- Child task mới được tạo với `parent_id` trỏ tới task cha.
- Trạng thái task cha có thể thay đổi theo quy tắc auto-cascade (xem UC-16).

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Thành viên | Trong trang chi tiết task cha, nhấn "Thêm child task", chọn loại và điền thông tin |
| 2 | Hệ thống | Xác thực dữ liệu và quyền (bug production chỉ PM/Admin/Tester mới thêm `linked_story_id`) |
| 3 | Hệ thống | Tạo child task, gắn `parent_id` |
| 4 | Hệ thống | Tự động tính `due_date` theo SLA: critical = 0 ngày, high = 1 ngày, medium/low = 2 ngày kể từ ngày tạo |
| 5 | Hệ thống | Kích hoạt auto-cascade: nếu cha đang `todo` → chuyển cha sang `in_progress` |
| 6 | Hệ thống | Nếu là bug con: gọi `KpiService::deductForBugCreated()` trừ điểm developer gốc |

### Luồng thay thế

**A1 — Tạo bug khi task cha không ở RTT:**
> Đối với bug thông thường (không phải production): hệ thống chặn nếu task cha chưa ở `ready_to_test`. Hiển thị thông báo lỗi.

---

## UC-14: Xem chi tiết task

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-14 |
| **Tên** | Xem chi tiết task |
| **Tác nhân chính** | Mọi thành viên dự án |
| **Mức độ** | Người dùng |

### Mô tả
Thành viên xem toàn bộ thông tin của một task: mô tả, trạng thái, lịch sử thay đổi, danh sách child task và bình luận.

### Tiền điều kiện
- Người dùng là thành viên của dự án chứa task.

### Hậu điều kiện
- Không thay đổi dữ liệu.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Thành viên | Truy cập `/projects/{project}/tasks/{task}` |
| 2 | Hệ thống | Hiển thị: thông tin cơ bản, trạng thái hiện tại, danh sách child task (với trạng thái từng con), lịch sử chuyển trạng thái, bình luận và tệp đính kèm |
| 3 | Hệ thống | Hiển thị các nút hành động phù hợp với vai trò của người dùng (chỉnh sửa, chuyển trạng thái, Pass/Fail, báo lỗi) |
| 4 | Hệ thống | Nếu là bug production: hiển thị banner cảnh báo gồm tên Developer gốc, Tester gốc, ngày story được done |

---

## UC-15: Chỉnh sửa task

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-15 |
| **Tên** | Chỉnh sửa thông tin task |
| **Tác nhân chính** | Admin, PM, người tạo task |
| **Mức độ** | Người dùng |

### Mô tả
Cập nhật các thông tin mô tả của task như tiêu đề, mô tả, mức ưu tiên, ngày, người nhận.

### Tiền điều kiện
- Người dùng là Admin, PM trong dự án, hoặc là người tạo task.

### Hậu điều kiện
- Thông tin task được cập nhật.
- Nếu người nhận thay đổi: thông báo "assigned" được gửi tới người nhận mới.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Chỉnh sửa các trường trong trang chi tiết task: tiêu đề, mô tả, mức ưu tiên, ngày, giờ ước tính, người nhận |
| 2 | Hệ thống | Xác thực dữ liệu |
| 3 | Hệ thống | Lưu thay đổi, ghi chú vào lịch sử nếu người nhận thay đổi |
| 4 | Hệ thống | Gửi thông báo tới người nhận mới nếu `assigned_to` thay đổi |

---

## UC-16: Chuyển trạng thái task

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-16 |
| **Tên** | Chuyển trạng thái task |
| **Tác nhân chính** | Thành viên dự án (theo vai trò và ràng buộc) |
| **Mức độ** | Người dùng |

### Mô tả
Thành viên chuyển trạng thái một task sang trạng thái mới. Hệ thống kiểm tra điều kiện nghiệp vụ và tự động cập nhật trạng thái task cha nếu cần (auto-cascade).

### Tiền điều kiện
- Người dùng là thành viên dự án.
- Trạng thái mới được chọn là hợp lệ theo vai trò và điều kiện.

### Hậu điều kiện
- Trạng thái task được cập nhật.
- Bản ghi lịch sử mới được thêm vào `task_histories`.
- Các timestamp tự động được cập nhật (`started_at`, `ready_at`, `done_at`).
- Auto-cascade kích hoạt nếu đây là child task.
- Thông báo được gửi đi tùy theo loại chuyển trạng thái.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Thành viên | Chọn trạng thái mới từ dropdown hoặc nhấn nút chuyển trạng thái |
| 2 | Hệ thống | Gọi `Task::transitionTo()`, kiểm tra các điều kiện nghiệp vụ |
| 3 | Hệ thống | Cập nhật `status`, ghi `task_histories`, cập nhật timestamp |
| 4 | Hệ thống | Gọi `Task::autoUpdateFromChildren()` để cascade lên task cha (nếu có) |
| 5 | Hệ thống | Gửi thông báo tương ứng |

### Ràng buộc chuyển trạng thái

| Chuyển sang | Điều kiện |
|------------|----------|
| `ready_to_test` | Tất cả child task không phải `bug` phải ở `done` |
| `review_approved` | Chỉ Tester hoặc Admin; chỉ task gốc; không còn bug con nào chưa `done` |
| `done` (task gốc) | Chỉ PM hoặc Admin; task phải đang ở `review_approved`; tất cả con (kể cả bug) phải `done` |
| `done` (task con) | Chỉ PM, Tester; bỏ qua bước `review_approved` |

### Quy tắc auto-cascade (Hệ thống tự động)

| Điều kiện | Hành động tự động |
|----------|------------------|
| Tất cả con non-bug → `done` | Cha tự chuyển → `ready_to_test` |
| Có con chuyển sang active khi cha đang `todo` | Cha tự chuyển → `in_progress` |
| Con bị hoàn nguyên khi cha đang `done` / `review_approved` / `ready_to_test` | Cha tự hoàn nguyên → `in_progress` |

### Quy tắc nghiệp vụ
- Bug con không được đếm vào điều kiện block RTT và auto-cascade, nhưng task gốc không thể `done` nếu còn bug con chưa `done`.
- Mỗi lần chuyển trạng thái được ghi vào `task_histories` với `from_status`, `to_status`, `changed_by`, `note`.
