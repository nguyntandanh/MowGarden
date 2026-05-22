MowGarden - Hệ thống Quản lý Cây cảnh
MowGarden là một ứng dụng quản lý thương mại điện tử chuyên biệt dành cho cửa hàng cây cảnh. 
Dự án được phát triển nhằm mục tiêu tối ưu hóa quy trình quản lý kho hàng và tích hợp AI Chatbot để tư vấn chăm sóc cây cho khách hàng.

🚀 Tính năng chính
Quản lý sản phẩm & Kho hàng: 
    Thêm/Xóa/Sửa sản phẩm 
    Quản lý số lượng tồn kho với tính năng "Xóa mềm" để bảo toàn dữ liệu lịch sử
AI Chatbot tư vấn: 
    Tích hợp API Gemini để tự động trả lời câu hỏi chăm sóc cây dựa trên dữ liệu sản phẩm trong kho
Thống kê: 
    Theo dõi doanh thu 
    lịch sử nhập kho
    phân loại sản phẩm theo mức tồn kho (Hết hàng/Sắp hết/An toàn)
Quản lý khách hàng: 
    Theo dõi lịch sử mua sắm và chi tiêu của khách hàng

🛠 Công nghệ sử dụng
Backend: PHP (Native/Thuần), PDO (MySQL).
Frontend: HTML5, CSS3, JavaScript (jQuery).
Database: MySQL.
AI Integration: Google Gemini API (Model: gemini-2.5-flash).
Icons: FontAwesome.

📋 Hướng dẫn cài đặt
1. Yêu cầu
XAMPP hoặc WAMP (PHP >= 7.4, MySQL).
2. Các bước triển khai
Clone dự án:
Bash
    git clone https://github.com/[Tên-User-Của-Bạn]/MowGarden.git

Thiết lập Database:
    Mở phpMyAdmin và tạo database mới tên là bancaycanhdb.
    Import file database.sql có sẵn trong thư mục dự án vào database vừa tạo.

Cấu hình kết nối:
    Mở file config/Database.php
    Cập nhật thông tin kết nối (username, password) phù hợp với cấu hình MySQL.

Cấu hình AI Chatbot:
    Mở file api/chatbot.php.
    Điền khóa API lấy từ Google AI Studio.

📂 Cấu trúc thư mục
Plaintext
/MowGarden
├── admin/          # Khu vực quản trị (Sản phẩm, Khách hàng, Kho, Khuyến mãi, Đơn hàng)
├── api/            # Xử lý các request AI Chatbot
├── client/         # Giao diện người dùng (Trang chủ, Giỏ hàng, Thanh toán)
├── config/         # Cấu hình kết nối Database
├── Images/         # Thư mục chứa ảnh sản phẩm
└── database.sql    # File SQL

👥 Tác giả
Nguyễn Tấn Danh - 2124802010658