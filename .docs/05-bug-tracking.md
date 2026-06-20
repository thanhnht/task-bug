# Đặc tả Use Case — Theo dõi Bug

---

## UC-17: Pass / Fail task (Tester)

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-17 |
| **Tên** | Đánh giá kết quả kiểm thử (Pass / Fail) |
| **Tác nhân chính** | Tester, Admin |
| **Mức độ** | Người dùng |

### Mô tả
Khi một task gốc loại `task` đang ở trạng thái `ready_to_test`, Tester đưa ra kết luận kiểm thử: **Pass** (đạt, chuyển sang Review Approved) hoặc **Fail** (không đạt, ghi nhận bug mới).

### Tiền điều kiện
- Task là task gốc, loại `task` (không phải bug, subtask hay các loại khác).
- Task đang ở trạng thái `ready_to_test`.
- Người dùng có vai trò Tester hoặc Admin trong dự án.

### Hậu điều kiện
- **Kết quả Pass:** Task chuyển sang `review_approved`. Thông báo được gửi tới PM.
- **Kết quả Fail:** Bug con mới được tạo, task cha hoàn nguyên về `in_progress`. Thông báo được gửi tới Developer.

### Luồng chính — Nhánh Pass

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Tester | Xem xét task đang RTT, nhấn nút "Pass — Đạt yêu cầu" |
| 2 | Hệ thống | Kiểm tra không còn bug con nào chưa `done` |
| 3 | Hệ thống | Gọi `Task::transitionTo('review_approved')` |
| 4 | Hệ thống | Gửi thông báo `review_approved` tới PM trong dự án |

### Luồng thay thế — Pass bị chặn (Bước 2)

**A1 — Còn bug con chưa đóng:**
> Hệ thống hiển thị lỗi "Còn N bug chưa đóng. Phải đóng hết bug trước khi Approved." Kết thúc use case.

### Luồng chính — Nhánh Fail

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Tester | Nhấn nút "Fail — Có lỗi" |
| 2 | Hệ thống | Mở modal "Ghi nhận Bug" với thông tin pre-fill: mã + tiêu đề story, tên developer gốc |
| 3 | Tester | Điền tiêu đề bug, mô tả chi tiết lỗi phát hiện |
| 4 | Tester | Nhấn "Lưu Bug" |
| 5 | Hệ thống | Tạo child bug, auto-gán cho developer gốc, tính SLA |
| 6 | Hệ thống | Task cha hoàn nguyên về `in_progress` |
| 7 | Hệ thống | Gửi thông báo tới developer |

### Quy tắc nghiệp vụ
- Nút Pass/Fail chỉ hiển thị khi `task.type = 'task'`. Các loại khác (bug, subtask, research...) dùng dropdown chuyển trạng thái thông thường.
- Developer gốc được xác định từ `task_histories`: người cuối cùng chuyển task sang `ready_to_test`.

---

## UC-18: Báo lỗi từ QA (Child Bug)

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-18 |
| **Tên** | Báo lỗi từ quá trình kiểm thử |
| **Tác nhân chính** | Tester, Admin |
| **Mức độ** | Người dùng |

### Mô tả
Tester tạo một bug con (child bug) gắn với task cha đang trong quá trình kiểm thử. Bug được tự động gán cho developer gốc của task cha và có SLA tính từ thời điểm tạo.

### Tiền điều kiện
- Task cha đang ở trạng thái `ready_to_test`.
- Người dùng có vai trò Tester hoặc Admin.

### Hậu điều kiện
- Bug mới được tạo với `parent_id` trỏ tới task cha, `type = bug`.
- Bug được tự động gán cho developer gốc.
- `due_date` được tính theo SLA của mức ưu tiên.
- KPI developer gốc bị trừ `-0.25` điểm (UC-20).
- Task cha hoàn nguyên về `in_progress`.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Tester | Trên trang chi tiết task đang RTT, sử dụng form "Báo lỗi" |
| 2 | Tester | Điền tiêu đề bug và mô tả lỗi |
| 3 | Hệ thống | Tạo child bug với `parent_id`, `assigned_to = devId`, tính `due_date` theo SLA |
| 4 | Hệ thống | Gọi `KpiService::deductForBugCreated()` trừ điểm developer |
| 5 | Hệ thống | Hoàn nguyên task cha về `in_progress` |
| 6 | Hệ thống | Gửi thông báo tới developer |

### Vòng đời bug con
```
todo → in_progress → ready_to_test → done
```
Bug con bỏ qua bước `review_approved`. PM hoặc Tester mới được chuyển bug con sang `done`.

---

## UC-19: Tạo Bug Production

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-19 |
| **Tên** | Tạo Bug Production |
| **Tác nhân chính** | Mọi thành viên dự án; PM/Admin gán linked story |
| **Mức độ** | Người dùng |

### Mô tả
Thành viên ghi nhận một lỗi phát sinh trên môi trường production mà không có task cha trong hệ thống. Bug này liên kết với story gốc đã bàn giao trước đó để truy xuất nguồn gốc và tự động trừ KPI developer + tester liên quan.

### Tiền điều kiện
- Người dùng là thành viên của dự án.
- Tồn tại ít nhất một story (task gốc) đã bàn giao trước đó (để có thể gán `linked_story_id`).

### Hậu điều kiện
- Bug production mới được tạo với `parent_id = null`, `is_production_bug = true`.
- Nếu `linked_story_id` được gán: KPI developer gốc và tester gốc của story đó bị trừ `-5` điểm mỗi người.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Thành viên | Truy cập `/projects/{project}/tasks/create`, chọn loại `bug` |
| 2 | Thành viên | Điền tiêu đề, mô tả, mức ưu tiên |
| 3 | PM/Admin | (Tùy chọn) Tìm kiếm story liên quan qua ô tìm kiếm (theo mã hoặc tiêu đề, hỗ trợ cả task cha và task con), chọn story |
| 4 | Hệ thống | Tạo task với `is_production_bug = true`, `parent_id = null`, `linked_story_id` (nếu được gán) |
| 5 | Hệ thống | Nếu có `linked_story_id`: tra `task_histories` của story xác định developer gốc và tester gốc |
| 6 | Hệ thống | Gọi `KpiService::deductForProductionBug()` trừ `-5` điểm cả hai người |

### Luồng thay thế

**A1 — Người dùng không phải PM/Admin cố gán linked story:**
> Hệ thống ẩn ô chọn linked story với vai trò Developer/Tester. Nếu cố tình gửi giá trị qua URL, server bỏ qua trường `linked_story_id`.

### Quy tắc nghiệp vụ
- Bug production là task gốc (không có cha), hoàn toàn khác với child bug phát sinh trong RTT.
- Bộ lọc trong danh sách phân biệt: "Bug (từ QA)" = `parent_id != null`; "Bug Production" = `is_production_bug = true AND parent_id = null`.
- Developer gốc = người cuối cùng trong `task_histories` chuyển story sang `ready_to_test`.
- Tester gốc = người cuối cùng trong `task_histories` chuyển story sang `review_approved` hoặc `done`.
