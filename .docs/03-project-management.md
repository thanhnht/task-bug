# Đặc tả Use Case — Quản lý Dự án

---

## UC-08: Tạo dự án

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-08 |
| **Tên** | Tạo dự án mới |
| **Tác nhân chính** | Admin |
| **Mức độ** | Quản lý |

### Mô tả
Admin khởi tạo một dự án phần mềm mới trong hệ thống, thiết lập thông tin cơ bản và phân công thành viên ban đầu với vai trò phù hợp.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin.

### Hậu điều kiện
- Dự án mới được tạo với mã tự sinh (ví dụ: `PRJ-001`), trạng thái `active`.
- Các thành viên được phân công được liên kết với dự án trong bảng `project_members`.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin | Truy cập `/projects/create`, điền thông tin: tên dự án (bắt buộc), mô tả, ngày bắt đầu, ngày kết thúc |
| 2 | Admin | Thêm thành viên ban đầu bằng cách chọn nhân viên và gán vai trò (PM / Developer / Tester) |
| 3 | Hệ thống | Xác thực dữ liệu (tên bắt buộc, ngày kết thúc ≥ ngày bắt đầu) |
| 4 | Hệ thống | Tự sinh mã dự án dạng `PRJ-NNN` |
| 5 | Hệ thống | Tạo bản ghi dự án với `status = active`, `created_by = Admin.id` |
| 6 | Hệ thống | Ghi các bản ghi thành viên vào bảng `project_members` (kèm `role`, `joined_at`) |
| 7 | Hệ thống | Chuyển hướng tới trang chi tiết dự án vừa tạo |

### Luồng thay thế

**A1 — Dữ liệu không hợp lệ (Bước 3):**
> Hệ thống hiển thị lỗi tương ứng (tên trống, ngày không hợp lệ). Quay lại Bước 1.

### Quy tắc nghiệp vụ
- Chỉ Admin mới được tạo dự án.
- Mỗi nhân viên chỉ có một vai trò trong một dự án (ràng buộc `UNIQUE(project_id, user_id)` trên `project_members`).

---

## UC-09: Xem danh sách và chi tiết dự án

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-09 |
| **Tên** | Xem danh sách và chi tiết dự án |
| **Tác nhân chính** | Mọi người dùng đã đăng nhập |
| **Mức độ** | Người dùng |

### Mô tả
Người dùng xem danh sách dự án mình có quyền truy cập và xem chi tiết từng dự án kèm danh sách task.

### Tiền điều kiện
- Người dùng đã đăng nhập và hoàn tất đổi mật khẩu lần đầu (nếu có).

### Hậu điều kiện
- Thông tin dự án và danh sách task được hiển thị theo phạm vi quyền của người dùng.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Người dùng | Truy cập `/projects` |
| 2 | Hệ thống | Lấy danh sách dự án theo quyền: Admin thấy tất cả; nhân viên chỉ thấy dự án mình tham gia |
| 3 | Người dùng | Chọn một dự án để xem chi tiết |
| 4 | Hệ thống | Hiển thị thông tin dự án, thanh tiến độ, danh sách task gốc có phân trang |
| 5 | Người dùng | Áp dụng bộ lọc: loại task, trạng thái, người nhận, khoảng ngày hoặc từ khóa tìm kiếm |
| 6 | Hệ thống | Cập nhật danh sách task theo điều kiện lọc, giữ nguyên tham số trên URL |

### Quy tắc nghiệp vụ
- Mặc định trang chi tiết lọc `type = task` (chỉ hiển thị task gốc).
- Bộ lọc loại phân biệt "Bug (từ QA)" và "Bug Production" thành hai tùy chọn riêng.
- Phân trang 10 task/trang, giữ nguyên tham số lọc khi chuyển trang.

---

## UC-10: Chỉnh sửa dự án

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-10 |
| **Tên** | Chỉnh sửa thông tin dự án |
| **Tác nhân chính** | Admin, PM (của dự án đó) |
| **Mức độ** | Quản lý |

### Mô tả
Admin hoặc PM cập nhật thông tin chung của dự án như tên, mô tả, ngày, trạng thái.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin hoặc PM trong dự án cần sửa.

### Hậu điều kiện
- Thông tin dự án được cập nhật trong cơ sở dữ liệu.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin/PM | Truy cập trang chi tiết dự án, nhấn "Chỉnh sửa" |
| 2 | Người dùng | Sửa các trường: tên, mô tả, trạng thái, ngày bắt đầu, ngày kết thúc |
| 3 | Hệ thống | Xác thực dữ liệu |
| 4 | Hệ thống | Lưu thay đổi, chuyển hướng về trang chi tiết với thông báo thành công |

### Luồng thay thế

**A1 — Dữ liệu không hợp lệ (Bước 3):**
> Hệ thống hiển thị lỗi. Quay lại Bước 2.

---

## UC-11: Quản lý thành viên dự án

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-11 |
| **Tên** | Quản lý thành viên dự án |
| **Tác nhân chính** | Admin, PM (của dự án đó) |
| **Mức độ** | Quản lý |

### Mô tả
Admin hoặc PM thêm, xóa hoặc thay đổi vai trò của thành viên trong dự án.

### Tiền điều kiện
- Người dùng đã đăng nhập với vai trò Admin hoặc PM trong dự án cần quản lý.

### Hậu điều kiện
- Bảng `project_members` được cập nhật phản ánh thay đổi thành viên / vai trò.

### Luồng chính — Thêm thành viên

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin/PM | Trên trang chi tiết / chỉnh sửa dự án, chọn nhân viên và vai trò, nhấn "Thêm" |
| 2 | Hệ thống | Kiểm tra nhân viên chưa là thành viên của dự án |
| 3 | Hệ thống | Ghi bản ghi mới vào `project_members` với `role` và `joined_at` |
| 4 | Hệ thống | Hiển thị thông báo thành công |

### Luồng chính — Xóa thành viên

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin/PM | Nhấn "Xóa" trên thành viên cần loại khỏi dự án |
| 2 | Hệ thống | Kiểm tra dự án vẫn còn ít nhất 1 PM sau khi xóa |
| 3 | Hệ thống | Xóa bản ghi khỏi `project_members` |

### Luồng chính — Đổi vai trò

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Admin/PM | Chọn vai trò mới cho thành viên, xác nhận |
| 2 | Hệ thống | Cập nhật cột `role` trong `project_members` |

### Luồng thay thế

**A1 — Nhân viên đã là thành viên (Bước 2 — Thêm):**
> Hệ thống trả về lỗi "Thành viên này đã có trong dự án." Kết thúc use case.

**A2 — Xóa PM duy nhất (Bước 2 — Xóa):**
> Hệ thống từ chối, hiển thị lỗi "Dự án phải có ít nhất 1 PM." Kết thúc use case.

### Quy tắc nghiệp vụ
- Admin hệ thống luôn có quyền PM trong mọi dự án dù không có trong `project_members`.
- Ba vai trò: `pm`, `developer`, `tester` (hằng số `Project::ROLE_PM`, `ROLE_DEVELOPER`, `ROLE_TESTER`).
