# Đặc tả Use Case — Bình luận & Tệp đính kèm

---

## UC-22: Viết bình luận và đính kèm tệp

| Thuộc tính | Nội dung |
|-----------|---------|
| **Mã UC** | UC-22 |
| **Tên** | Viết bình luận và đính kèm tệp vào task |
| **Tác nhân chính** | Mọi thành viên dự án |
| **Mức độ** | Người dùng |

### Mô tả
Thành viên dự án có thể thêm bình luận vào bất kỳ task nào (task gốc, bug, subtask, hoặc bất kỳ loại task nào). Bình luận hỗ trợ định dạng rich text, chèn ảnh trực tiếp vào nội dung, và đính kèm tệp.

### Tiền điều kiện
- Người dùng là thành viên của dự án chứa task.

### Hậu điều kiện
- Bình luận mới được lưu vào bảng `comments`, liên kết với task.
- Các tệp đính kèm (nếu có) được lưu vào `comment_attachments` và lưu vật lý tại `storage/comments/{task_id}/`.

### Luồng chính

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Thành viên | Mở trang chi tiết task, cuộn xuống phần bình luận |
| 2 | Thành viên | Nhập nội dung bình luận trong trình soạn thảo (hỗ trợ: chữ đậm, nghiêng, danh sách, heading) |
| 3 | Thành viên | (Tùy chọn) Chèn ảnh inline: nhấn nút chèn ảnh, chọn file, hệ thống upload và nhúng URL ảnh vào nội dung |
| 4 | Thành viên | (Tùy chọn) Đính kèm tệp: kéo thả hoặc chọn file từ máy |
| 5 | Thành viên | Nhấn "Gửi bình luận" |
| 6 | Hệ thống | Xác thực nội dung và tệp đính kèm |
| 7 | Hệ thống | Lưu bình luận vào `comments`, lưu tệp đính kèm vào `comment_attachments` và disk |
| 8 | Hệ thống | Làm mới danh sách bình luận |

### Luồng thay thế

**A1 — Tệp vượt quá giới hạn kích thước (Bước 6):**
> Hệ thống từ chối, hiển thị lỗi "Tệp không được vượt quá 20 MB." Quay lại Bước 4.

**A2 — Định dạng tệp không được hỗ trợ (Bước 6):**
> Hệ thống từ chối, hiển thị lỗi "Định dạng tệp không được hỗ trợ." Quay lại Bước 4.

### Luồng phụ — Xóa bình luận

| Bước | Tác nhân | Hành động |
|------|---------|----------|
| 1 | Thành viên / Admin | Nhấn nút "Xóa" trên bình luận |
| 2 | Hệ thống | Kiểm tra quyền: người viết bình luận hoặc Admin |
| 3 | Hệ thống | Xóa bản ghi `comments`, xóa `comment_attachments` kèm tệp vật lý |

**A1 — Người dùng không phải tác giả và không phải Admin (Bước 2):**
> Hệ thống từ chối với lỗi 403. Kết thúc use case.

### Quy tắc nghiệp vụ

**Định dạng tệp đính kèm được chấp nhận:**
- Ảnh: `jpg`, `jpeg`, `png`, `gif`, `webp`
- Tài liệu: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`
- Khác: `zip`, `txt`, `csv`

**Giới hạn:** Tối đa 20 MB mỗi tệp.
