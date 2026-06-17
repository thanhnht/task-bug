<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    private int $taskIdNext = 1;
    private string $currentMonth;
    private string $prevMonth;

    // Tên người Việt thật
    private array $PEOPLE = [
        // [full_name, username, email_prefix, role_hint]
        ['Nguyễn Quang Hải',    'hainq',    'nguyenquanghai',    'admin'],
        ['Trần Minh Tuấn',      'tuantm',   'tranminhtuan',      'pm'],
        ['Lê Thị Hương',        'huongle',  'lethihuong',        'pm'],
        ['Phạm Văn Đức',        'ducpv',    'phamvanduc',        'pm'],
        ['Hoàng Thị Lan',       'lanht',    'hoangthilan',       'pm'],
        ['Vũ Quốc Bảo',         'baovq',    'vuquocbao',         'pm'],
        ['Đặng Hồng Sơn',       'sondh',    'danghongson',       'dev'],
        ['Bùi Văn Khoa',        'khoabv',   'buivankhoa',        'dev'],
        ['Ngô Thị Thu',         'thunt',    'ngothithu',         'dev'],
        ['Dương Minh Khải',     'khaidm',   'duongminhkhai',     'dev'],
        ['Lý Văn Thịnh',        'thinhlv',  'lyvanthinh',        'dev'],
        ['Phan Thị Cẩm Tú',     'tupt',     'phanthicamtu',      'dev'],
        ['Trịnh Quốc Việt',     'viettq',   'trinhquocviet',     'dev'],
        ['Cao Xuân Trường',     'truongcx', 'caoxuantruong',     'dev'],
        ['Đinh Thị Ngọc Anh',   'anhdt',    'dinhthingochanh',   'dev'],
        ['Lưu Thị Mỹ Dung',     'dunglt',   'luuthimydung',      'dev'],
        ['Mai Thanh Hùng',      'hungmt',   'maithanhhung',      'dev'],
        ['Tô Ngọc Linh',        'linht',    'tongoclinh',        'dev'],
        ['Nghiêm Văn Tùng',     'tungnv',   'nghiemvantung',     'tester'],
        ['Chu Thị Kim Anh',     'anhct',    'chithikimanh',      'tester'],
        ['Hồ Minh Đức',         'duchm',    'hominhduc',         'tester'],
        ['Nguyễn Thị Phương',   'phuongnt', 'nguyenthiphuong',   'tester'],
        ['Trương Văn Nam',      'namtv',    'truongvannam',      'tester'],
        ['Đỗ Thị Thanh Hà',     'hadt',     'dothithanhha',      'tester'],
        ['Vương Minh Quân',     'quanvm',   'vuongminhquan',     'tester'],
        ['Phan Lê Anh Dũng',    'dungpla',  'phanleandhdung',    'tester'],
        ['Lê Hoàng Phúc',       'phuclh',   'lehoangphuc',       'tester'],
        ['Phùng Thị Yến',       'yenpt',    'phungthiyen',       'tester'],
        ['Bạch Ngọc Hân',       'hanbn',    'bachngochan',       'extra'],
        ['Kiều Thanh Tâm',      'tamkt',    'kieuthanhtam',      'extra'],
    ];

    public function run(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->prevMonth    = now()->subMonth()->format('Y-m');

        $this->clearData();

        $users    = $this->createUsers();
        $projects = $this->createProjects($users);

        foreach ($projects as $proj) {
            $this->seedProject($proj, $users);
        }

        $this->command->info('DemoSeeder xong. 30 users, 5 projects, ~47 tasks/project.');
    }

    // ── Cleanup ──────────────────────────────────────────────────────────────

    private function clearData(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('kpi_transactions')->truncate();
        DB::table('task_histories')->truncate();
        DB::table('tasks')->truncate();
        DB::table('project_members')->truncate();
        DB::table('projects')->truncate();
        DB::table('users')->where('email', 'like', '%@yopmail.com')->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->taskIdNext = 1;
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    private function createUsers(): array
    {
        $now  = now();
        $hash = Hash::make('Password1@');
        $rows = [];

        foreach ($this->PEOPLE as $p) {
            $rows[] = [
                'full_name'      => $p[0],
                'username'       => $p[1],
                'email'          => $p[2] . '@yopmail.com',
                'password'       => $hash,
                'role'           => $p[3] === 'admin' ? 'admin' : 'employee',
                'is_first_login' => 0,
                'is_active'      => 1,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        DB::table('users')->insert($rows);

        $all = DB::table('users')->where('email', 'like', '%@yopmail.com')
            ->orderBy('id')->get();

        return [
            'admin'   => $all[0],
            'pms'     => $all->slice(1, 5)->values(),
            'devs'    => $all->slice(6, 12)->values(),
            'testers' => $all->slice(18, 10)->values(),
        ];
    }

    // ── Projects ──────────────────────────────────────────────────────────────

    private function createProjects(array $users): array
    {
        $now  = now();
        $defs = [
            [
                'code' => 'SHOP',
                'name' => 'Sàn Thương Mại Điện Tử',
                'desc' => 'Xây dựng nền tảng mua sắm trực tuyến cho thị trường Việt Nam: quản lý sản phẩm, đơn hàng, thanh toán và vận chuyển.',
                'pm'   => 0, 'devs' => [0,1,2],   'testers' => [0,1],
            ],
            [
                'code' => 'ERP',
                'name' => 'Hệ Thống Quản Lý Doanh Nghiệp',
                'desc' => 'Phần mềm ERP nội bộ: quản lý nhân sự, kế toán, kho hàng và chuỗi cung ứng cho công ty sản xuất.',
                'pm'   => 1, 'devs' => [3,4,5],   'testers' => [2,3],
            ],
            [
                'code' => 'MOB',
                'name' => 'Ứng Dụng Di Động FinTech',
                'desc' => 'App tài chính cá nhân trên iOS & Android: theo dõi chi tiêu, đầu tư quỹ mở và chuyển tiền nhanh.',
                'pm'   => 2, 'devs' => [6,7,8],   'testers' => [4,5],
            ],
            [
                'code' => 'CRM',
                'name' => 'Hệ Thống CRM Bán Hàng',
                'desc' => 'Nền tảng quản lý quan hệ khách hàng: pipeline bán hàng, tự động hoá email marketing và báo cáo doanh thu.',
                'pm'   => 3, 'devs' => [9,10,11], 'testers' => [6,7],
            ],
            [
                'code' => 'PAY',
                'name' => 'Cổng Thanh Toán Trực Tuyến',
                'desc' => 'Xây dựng payment gateway tích hợp VNPay, Momo, ZaloPay với tính năng phát hiện gian lận và đối soát tự động.',
                'pm'   => 4, 'devs' => [0,3,6],   'testers' => [8,9],
            ],
        ];

        $projects = [];
        foreach ($defs as $def) {
            $projId = DB::table('projects')->insertGetId([
                'code'        => $def['code'],
                'name'        => $def['name'],
                'description' => $def['desc'],
                'status'      => 'active',
                'created_by'  => $users['admin']->id,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $pm      = $users['pms'][$def['pm']];
            $devList = collect($def['devs'])->map(fn($i) => $users['devs'][$i]);
            $tstList = collect($def['testers'])->map(fn($i) => $users['testers'][$i]);

            $members = [[
                'project_id' => $projId, 'user_id' => $pm->id,
                'role' => 'pm', 'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now,
            ]];
            foreach ($devList as $d) {
                $members[] = ['project_id' => $projId, 'user_id' => $d->id, 'role' => 'developer', 'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now];
            }
            foreach ($tstList as $t) {
                $members[] = ['project_id' => $projId, 'user_id' => $t->id, 'role' => 'tester', 'joined_at' => $now, 'created_at' => $now, 'updated_at' => $now];
            }
            DB::table('project_members')->insert($members);

            $projects[] = [
                'id'      => $projId,
                'code'    => $def['code'],
                'pm'      => $pm,
                'devs'    => $devList,
                'testers' => $tstList,
            ];
        }

        return $projects;
    }

    // ── Task titles theo domain ────────────────────────────────────────────────

    private function taskTitles(string $projectCode): array
    {
        $map = [
            'SHOP' => [
                'Xây dựng module đăng ký / đăng nhập khách hàng',
                'Tích hợp xác thực OTP qua SMS (VIETTEL, VNPT)',
                'Thiết kế trang chủ và danh mục sản phẩm',
                'Xây dựng chức năng tìm kiếm và lọc sản phẩm',
                'Triển khai giỏ hàng và quy trình đặt hàng',
                'Tích hợp thanh toán VNPay và COD',
                'Xây dựng trang quản lý đơn hàng cho người bán',
                'Hệ thống đánh giá và nhận xét sản phẩm',
                'Chức năng áp dụng mã giảm giá và voucher',
                'Trang dashboard thống kê doanh thu cho seller',
                'Tích hợp API vận chuyển GHN / GHTK',
                'Hệ thống thông báo đơn hàng qua email & Zalo',
                'Xây dựng chương trình khách hàng thân thiết (điểm thưởng)',
                'Tối ưu hiệu năng tải trang sản phẩm (lazy load, CDN)',
                'Chức năng so sánh sản phẩm',
                'Module quản lý tồn kho theo warehouse',
                'Tích hợp Facebook Pixel & Google Analytics',
                'Xây dựng trang flash sale và đếm ngược',
            ],
            'ERP' => [
                'Module quản lý nhân viên và hồ sơ lao động',
                'Chức năng chấm công bằng vân tay / QR code',
                'Hệ thống tính lương và phúc lợi tự động',
                'Quản lý nghỉ phép và phê duyệt trực tuyến',
                'Module kế toán: quản lý hóa đơn và công nợ',
                'Báo cáo tài chính tháng / quý tự động',
                'Quản lý kho hàng: nhập / xuất / tồn',
                'Tích hợp mã vạch và máy quét QR cho kho',
                'Module mua hàng và đặt lệnh với nhà cung cấp',
                'Hệ thống phê duyệt đa cấp (workflow engine)',
                'Quản lý tài sản cố định và khấu hao',
                'Chức năng lập kế hoạch sản xuất (MRP)',
                'Báo cáo KPI nhân sự theo phòng ban',
                'Tích hợp email nội bộ và thông báo hệ thống',
                'Module đào tạo và đánh giá năng lực nhân viên',
                'Quản lý hợp đồng lao động và gia hạn',
                'Hệ thống quản lý dự án nội bộ',
                'Chức năng xuất báo cáo PDF / Excel',
            ],
            'MOB' => [
                'Thiết kế UI/UX màn hình onboarding và đăng ký',
                'Tích hợp xác thực sinh trắc học (Face ID, vân tay)',
                'Xây dựng màn hình tổng quan tài chính cá nhân',
                'Module theo dõi và phân loại chi tiêu tự động',
                'Chức năng liên kết tài khoản ngân hàng (Open Banking)',
                'Tích hợp đầu tư quỹ mở (Techcom Fund, VinaMoney)',
                'Xây dựng tính năng chuyển tiền nhanh 24/7',
                'Module nạp tiền điện thoại và thanh toán hóa đơn',
                'Tích hợp thông báo push (FCM / APNS)',
                'Chức năng lập ngân sách và cảnh báo vượt chi',
                'Biểu đồ phân tích xu hướng tài chính cá nhân',
                'Module bảo mật: xác thực 2 lớp và mã PIN',
                'Tích hợp chatbot hỗ trợ khách hàng',
                'Chức năng chia sẻ chi tiêu theo nhóm (Splitwise)',
                'Tối ưu tốc độ khởi động app và giảm kích thước APK',
                'Màn hình cài đặt và tuỳ chỉnh thông báo',
                'Module lịch sử giao dịch và xuất sao kê',
                'Tích hợp Google Pay và Apple Pay',
            ],
            'CRM' => [
                'Xây dựng module quản lý lead và khách hàng tiềm năng',
                'Thiết kế pipeline bán hàng dạng Kanban board',
                'Chức năng ghi nhận cuộc gọi và lịch sử tương tác',
                'Tích hợp gửi email marketing hàng loạt (Mailchimp API)',
                'Module báo giá và tạo hợp đồng tự động',
                'Hệ thống nhắc việc và lịch hẹn với khách hàng',
                'Dashboard doanh thu theo nhân viên kinh doanh',
                'Tích hợp Zalo OA để nhắn tin khách hàng',
                'Chức năng phân tích win/loss theo sản phẩm',
                'Module quản lý campaign marketing đa kênh',
                'Xây dựng hệ thống ticket hỗ trợ sau bán hàng',
                'Tích hợp Facebook Lead Ads tự động import lead',
                'Báo cáo funnel chuyển đổi theo giai đoạn',
                'Chức năng phân quyền và territory theo vùng miền',
                'Module onboarding khách hàng mới (checklist)',
                'Tích hợp Google Calendar đồng bộ lịch hẹn',
                'Hệ thống chấm điểm lead (lead scoring AI)',
                'Xuất báo cáo tháng gửi tự động cho giám đốc',
            ],
            'PAY' => [
                'Xây dựng core payment processing engine',
                'Tích hợp cổng VNPay (QR, ATM, thẻ tín dụng)',
                'Tích hợp ví điện tử MoMo và ZaloPay',
                'Module phát hiện gian lận theo thời gian thực (Fraud Detection)',
                'Hệ thống đối soát giao dịch tự động cuối ngày',
                'Xây dựng API thanh toán RESTful cho merchant',
                'Dashboard giám sát giao dịch real-time',
                'Cơ chế retry và xử lý timeout giao dịch',
                'Module quản lý hoàn tiền (refund) tự động',
                'Tích hợp 3D Secure và xác thực OTP ngân hàng',
                'Hệ thống quản lý merchant và cấu hình tỉ lệ phí',
                'Xây dựng sandbox environment cho merchant test',
                'Module báo cáo doanh thu và hoa hồng theo merchant',
                'Tích hợp hệ thống NAPAS cho giao dịch liên ngân hàng',
                'Xây dựng webhook notification cho merchant',
                'Module quản lý tranh chấp (chargeback) giao dịch',
                'Tối ưu hiệu năng: xử lý 1000 TPS đồng thời',
                'Chứng chỉ bảo mật PCI-DSS Level 1',
            ],
        ];

        return $map[$projectCode] ?? $map['SHOP'];
    }

    private function subtaskTitles(): array
    {
        return [
            'Thiết kế database schema và migration',
            'Viết API endpoint (backend Laravel)',
            'Xây dựng giao diện người dùng (frontend)',
            'Viết unit test và integration test',
            'Code review và tối ưu query SQL',
            'Tích hợp và kiểm tra trên môi trường staging',
            'Viết tài liệu API (Swagger / Postman)',
            'Xử lý edge case và validate dữ liệu đầu vào',
            'Tối ưu hiệu năng và caching (Redis)',
            'Cấu hình CI/CD pipeline cho module mới',
        ];
    }

    private function bugTitles(): array
    {
        return [
            'Lỗi validation không hiển thị message cho người dùng',
            'API trả về 500 khi payload rỗng',
            'Giao diện bị vỡ layout trên màn hình mobile 375px',
            'Dữ liệu không được lưu khi bấm nút Lưu lần 2',
            'Tính toán sai số tiền khi áp dụng mã giảm giá',
            'Session bị mất sau khi refresh trang',
            'Ảnh không upload được khi dung lượng > 2MB',
            'Lỗi phân trang: trang cuối hiển thị dữ liệu sai',
            'Dropdown không hiển thị đúng khi có dấu tiếng Việt',
            'Timeout khi query danh sách > 10.000 bản ghi',
            'Nút back trình duyệt gây lỗi form resubmit',
            'Email thông báo bị gửi trùng lặp 2 lần',
            'Lỗi CORS khi gọi API từ domain khác',
            'Filter ngày tháng tính sai timezone UTC+7',
        ];
    }

    private function prodBugTitles(): array
    {
        return [
            '[PROD] Không thể thanh toán sau 22:00 – ảnh hưởng toàn bộ user',
            '[PROD] Dữ liệu khách hàng hiển thị nhầm giữa các tài khoản',
            '[PROD] Báo cáo doanh thu tháng tính sai do lỗi múi giờ',
            '[PROD] App crash khi mở màn hình lịch sử giao dịch (iOS 17)',
            '[PROD] Email xác nhận đơn hàng không được gửi từ 8h sáng',
        ];
    }

    // ── Seed một project ──────────────────────────────────────────────────────

    private function seedProject(array $proj, array $users): void
    {
        $pid     = $proj['id'];
        $code    = $proj['code'];
        $pm      = $proj['pm'];
        $devs    = $proj['devs'];
        $testers = $proj['testers'];
        $admin   = $users['admin'];

        $titles    = $this->taskTitles($code);
        $subTitles = $this->subtaskTitles();
        $bugTitles = $this->bugTitles();
        $titleIdx  = 0;
        $subIdx    = 0;
        $bugIdx    = 0;

        $nextTitle = function () use (&$titles, &$titleIdx) {
            $t = $titles[$titleIdx % count($titles)];
            $titleIdx++;
            return $t;
        };
        $nextSub = function () use (&$subTitles, &$subIdx) {
            $t = $subTitles[$subIdx % count($subTitles)];
            $subIdx++;
            return $t;
        };
        $nextBug = function () use (&$bugTitles, &$bugIdx) {
            $t = $bugTitles[$bugIdx % count($bugTitles)];
            $bugIdx++;
            return $t;
        };

        // ── 4 DONE tasks ──────────────────────────────────────────────────────
        for ($i = 1; $i <= 4; $i++) {
            $dev     = $devs[($i - 1) % count($devs)];
            $tester  = $testers[($i - 1) % count($testers)];
            $daysAgo = 70 - ($i * 12);
            $late    = ($i % 2 === 0);
            $dueAgo  = $late ? $daysAgo + 4 : $daysAgo - 2;
            $doneAgo = $daysAgo - 5;

            $taskId = $this->insertTask([
                'code'        => $this->nextCode($code),
                'project_id'  => $pid,
                'type'        => 'task',
                'title'       => $nextTitle(),
                'description' => 'Hoàn thiện đầy đủ theo yêu cầu kỹ thuật đã thống nhất trong buổi kickoff. Xem thêm tài liệu đặc tả tại Confluence.',
                'priority'    => ['medium','high','critical','medium'][$i % 4],
                'status'      => 'done',
                'created_by'  => $pm->id,
                'assigned_to' => $dev->id,
                'confirmed_by'=> $pm->id,
                'start_date'  => now()->subDays($daysAgo + 5),
                'due_date'    => now()->subDays($dueAgo),
                'started_at'  => now()->subDays($daysAgo + 4),
                'ready_at'    => now()->subDays($doneAgo + 3),
                'done_at'     => now()->subDays($doneAgo),
                'created_at'  => now()->subDays($daysAgo + 5),
                'updated_at'  => now()->subDays($doneAgo),
            ]);

            $this->recordHistory($taskId, null, 'todo', $pm->id, now()->subDays($daysAgo + 5));
            $this->recordHistory($taskId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo + 4));
            $this->recordHistory($taskId, 'in_progress', 'ready_to_test', $dev->id, now()->subDays($doneAgo + 3));
            $this->recordHistory($taskId, 'ready_to_test', 'review_approved', $tester->id, now()->subDays($doneAgo + 1));
            $this->recordHistory($taskId, 'review_approved', 'done', $pm->id, now()->subDays($doneAgo));

            if ($late) {
                $doneAt  = now()->subDays($doneAgo);
                $dueDate = now()->subDays($dueAgo);
                $lateDays = (int) ceil($dueDate->diffInDays($doneAt));
                $this->kpiRecord($dev->id, $pid, $taskId, -2.0 * $lateDays,
                    "Task \"{$this->shortTitle($taskId)}\" hoàn thành trễ {$lateDays} ngày",
                    $doneAt->format('Y-m'), $doneAt);
            }

            // 2 subtasks
            for ($s = 0; $s < 2; $s++) {
                $subId = $this->insertTask([
                    'code'        => $this->nextCode($code),
                    'project_id'  => $pid,
                    'parent_id'   => $taskId,
                    'type'        => 'subtask',
                    'title'       => $nextSub(),
                    'priority'    => 'medium',
                    'status'      => 'done',
                    'created_by'  => $dev->id,
                    'assigned_to' => $dev->id,
                    'start_date'  => now()->subDays($daysAgo + 3),
                    'due_date'    => now()->subDays($doneAgo + 4),
                    'started_at'  => now()->subDays($daysAgo + 3),
                    'done_at'     => now()->subDays($doneAgo + 4),
                    'created_at'  => now()->subDays($daysAgo + 4),
                    'updated_at'  => now()->subDays($doneAgo + 4),
                ]);
                $this->recordHistory($subId, null, 'todo', $dev->id, now()->subDays($daysAgo + 4));
                $this->recordHistory($subId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo + 3));
                $this->recordHistory($subId, 'in_progress', 'done', $dev->id, now()->subDays($doneAgo + 4));
            }

            // 1 bug (đã đóng)
            $bugDev = $devs[$i % count($devs)];
            $bugId  = $this->insertTask([
                'code'        => $this->nextCode($code),
                'project_id'  => $pid,
                'parent_id'   => $taskId,
                'type'        => 'bug',
                'title'       => $nextBug(),
                'priority'    => 'high',
                'status'      => 'done',
                'created_by'  => $tester->id,
                'assigned_to' => $bugDev->id,
                'start_date'  => now()->subDays($doneAgo + 5),
                'due_date'    => now()->subDays($doneAgo + 4),
                'done_at'     => now()->subDays($doneAgo + 3),
                'created_at'  => now()->subDays($doneAgo + 5),
                'updated_at'  => now()->subDays($doneAgo + 3),
            ]);
            $this->recordHistory($bugId, null, 'todo', $tester->id, now()->subDays($doneAgo + 5));
            $this->recordHistory($bugId, 'todo', 'in_progress', $bugDev->id, now()->subDays($doneAgo + 4));
            $this->recordHistory($bugId, 'in_progress', 'done', $bugDev->id, now()->subDays($doneAgo + 3));

            $this->kpiRecord($dev->id, $pid, $taskId, -0.25,
                "Bug phát sinh trên task: " . $this->getTaskTitle($taskId),
                now()->subDays($doneAgo + 5)->format('Y-m'), now()->subDays($doneAgo + 5));
        }

        // ── 2 REVIEW_APPROVED tasks ───────────────────────────────────────────
        for ($i = 1; $i <= 2; $i++) {
            $dev    = $devs[$i % count($devs)];
            $tester = $testers[$i % count($testers)];
            $daysAgo = 22 + ($i * 5);

            $taskId = $this->insertTask([
                'code'        => $this->nextCode($code),
                'project_id'  => $pid,
                'type'        => 'task',
                'title'       => $nextTitle(),
                'description' => 'Tester đã xác nhận pass toàn bộ test case. Đang chờ PM review lần cuối trước khi merge vào nhánh release.',
                'priority'    => 'high',
                'status'      => 'review_approved',
                'created_by'  => $pm->id,
                'assigned_to' => $tester->id,
                'start_date'  => now()->subDays($daysAgo + 3),
                'due_date'    => now()->subDays($daysAgo - 5),
                'started_at'  => now()->subDays($daysAgo + 2),
                'ready_at'    => now()->subDays($daysAgo - 2),
                'created_at'  => now()->subDays($daysAgo + 3),
                'updated_at'  => now()->subDays($daysAgo - 2),
            ]);
            $this->recordHistory($taskId, null, 'todo', $pm->id, now()->subDays($daysAgo + 3));
            $this->recordHistory($taskId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo + 2));
            $this->recordHistory($taskId, 'in_progress', 'ready_to_test', $dev->id, now()->subDays($daysAgo));
            $this->recordHistory($taskId, 'ready_to_test', 'review_approved', $tester->id, now()->subDays($daysAgo - 2));

            for ($s = 0; $s < 2; $s++) {
                $subId = $this->insertTask([
                    'code'        => $this->nextCode($code),
                    'project_id'  => $pid,
                    'parent_id'   => $taskId,
                    'type'        => 'subtask',
                    'title'       => $nextSub(),
                    'priority'    => 'medium',
                    'status'      => 'done',
                    'created_by'  => $dev->id,
                    'assigned_to' => $dev->id,
                    'done_at'     => now()->subDays($daysAgo + 1),
                    'created_at'  => now()->subDays($daysAgo + 3),
                    'updated_at'  => now()->subDays($daysAgo + 1),
                ]);
                $this->recordHistory($subId, null, 'todo', $dev->id, now()->subDays($daysAgo + 3));
                $this->recordHistory($subId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo + 2));
                $this->recordHistory($subId, 'in_progress', 'done', $dev->id, now()->subDays($daysAgo + 1));
            }
        }

        // ── 3 READY_TO_TEST tasks ─────────────────────────────────────────────
        for ($i = 1; $i <= 3; $i++) {
            $dev    = $devs[$i % count($devs)];
            $tester = $testers[($i + 1) % count($testers)];
            $daysAgo = 10 + ($i * 2);
            $rttAt   = now()->subDays($daysAgo - 2);

            $taskId = $this->insertTask([
                'code'        => $this->nextCode($code),
                'project_id'  => $pid,
                'type'        => 'task',
                'title'       => $nextTitle(),
                'description' => 'Dev đã hoàn thành, đang chờ tester verify trên môi trường staging. Xem hướng dẫn test case tại Google Sheet đính kèm.',
                'priority'    => 'medium',
                'status'      => 'ready_to_test',
                'created_by'  => $pm->id,
                'assigned_to' => $dev->id,
                'start_date'  => now()->subDays($daysAgo + 3),
                'due_date'    => now()->subDays($daysAgo - 4),
                'started_at'  => now()->subDays($daysAgo + 2),
                'ready_at'    => $rttAt,
                'created_at'  => now()->subDays($daysAgo + 3),
                'updated_at'  => $rttAt,
            ]);
            $this->recordHistory($taskId, null, 'todo', $pm->id, now()->subDays($daysAgo + 3));
            $this->recordHistory($taskId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo + 2));
            $this->recordHistory($taskId, 'in_progress', 'ready_to_test', $dev->id, $rttAt);

            for ($s = 0; $s < 2; $s++) {
                $subId = $this->insertTask([
                    'code'        => $this->nextCode($code),
                    'project_id'  => $pid,
                    'parent_id'   => $taskId,
                    'type'        => 'subtask',
                    'title'       => $nextSub(),
                    'priority'    => 'medium',
                    'status'      => 'done',
                    'created_by'  => $dev->id,
                    'assigned_to' => $dev->id,
                    'done_at'     => now()->subDays($daysAgo),
                    'created_at'  => now()->subDays($daysAgo + 2),
                    'updated_at'  => now()->subDays($daysAgo),
                ]);
                $this->recordHistory($subId, null, 'todo', $dev->id, now()->subDays($daysAgo + 2));
                $this->recordHistory($subId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo + 1));
                $this->recordHistory($subId, 'in_progress', 'done', $dev->id, now()->subDays($daysAgo));
            }

            // 1 bug đang mở
            $openBugId = $this->insertTask([
                'code'        => $this->nextCode($code),
                'project_id'  => $pid,
                'parent_id'   => $taskId,
                'type'        => 'bug',
                'title'       => $nextBug(),
                'priority'    => 'high',
                'status'      => 'in_progress',
                'created_by'  => $tester->id,
                'assigned_to' => $dev->id,
                'due_date'    => now()->addDay(),
                'created_at'  => now()->subDays($daysAgo - 3),
                'updated_at'  => now()->subDays($daysAgo - 3),
            ]);
            $this->recordHistory($openBugId, null, 'todo', $tester->id, now()->subDays($daysAgo - 3));
            $this->recordHistory($openBugId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo - 3));

            $this->kpiRecord($dev->id, $pid, $taskId, -0.25,
                "Bug phát sinh trên task: " . $this->getTaskTitle($taskId),
                now()->subDays($daysAgo - 3)->format('Y-m'), now()->subDays($daysAgo - 3));

            // RTT soak KPI
            $soakHours = (int) $rttAt->diffInHours(now());
            $soakDays  = max(0, floor(($soakHours - 48) / 24));
            if ($soakDays > 0) {
                $this->kpiRecord($tester->id, $pid, $taskId, -0.5 * $soakDays,
                    "Ngâm RTT {$soakDays} ngày, task: " . $this->getTaskTitle($taskId),
                    $this->currentMonth, now()->subDay());
            }
        }

        // ── 3 IN_PROGRESS tasks ───────────────────────────────────────────────
        for ($i = 1; $i <= 3; $i++) {
            $dev = $devs[($i + 2) % count($devs)];
            $daysAgo = 4 + $i;

            $taskId = $this->insertTask([
                'code'        => $this->nextCode($code),
                'project_id'  => $pid,
                'type'        => 'task',
                'title'       => $nextTitle(),
                'description' => 'Đang trong quá trình phát triển. Developer đang xử lý phần logic core, dự kiến hoàn thành cuối sprint.',
                'priority'    => ['medium','high','low'][$i % 3],
                'status'      => 'in_progress',
                'created_by'  => $pm->id,
                'assigned_to' => $dev->id,
                'start_date'  => now()->subDays($daysAgo + 1),
                'due_date'    => now()->addDays(5 + $i),
                'started_at'  => now()->subDays($daysAgo),
                'created_at'  => now()->subDays($daysAgo + 1),
                'updated_at'  => now()->subDays($daysAgo),
            ]);
            $this->recordHistory($taskId, null, 'todo', $pm->id, now()->subDays($daysAgo + 1));
            $this->recordHistory($taskId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo));

            for ($s = 0; $s < 2; $s++) {
                $subStatus = ($s === 0) ? 'in_progress' : 'todo';
                $subId = $this->insertTask([
                    'code'        => $this->nextCode($code),
                    'project_id'  => $pid,
                    'parent_id'   => $taskId,
                    'type'        => 'subtask',
                    'title'       => $nextSub(),
                    'priority'    => 'medium',
                    'status'      => $subStatus,
                    'created_by'  => $dev->id,
                    'assigned_to' => $dev->id,
                    'started_at'  => $subStatus === 'in_progress' ? now()->subDays($daysAgo - 1) : null,
                    'created_at'  => now()->subDays($daysAgo),
                    'updated_at'  => now()->subDays($daysAgo),
                ]);
                $this->recordHistory($subId, null, 'todo', $dev->id, now()->subDays($daysAgo));
                if ($subStatus === 'in_progress') {
                    $this->recordHistory($subId, 'todo', 'in_progress', $dev->id, now()->subDays($daysAgo - 1));
                }
            }
        }

        // ── 3 TODO tasks ──────────────────────────────────────────────────────
        for ($i = 1; $i <= 3; $i++) {
            $dev = $devs[$i % count($devs)];
            $taskId = $this->insertTask([
                'code'        => $this->nextCode($code),
                'project_id'  => $pid,
                'type'        => 'task',
                'title'       => $nextTitle(),
                'description' => 'Task đã được PM tạo và phân công. Developer cần đọc kỹ tài liệu kỹ thuật trước khi bắt đầu.',
                'priority'    => 'medium',
                'status'      => 'todo',
                'created_by'  => $pm->id,
                'assigned_to' => $dev->id,
                'start_date'  => now()->addDays($i),
                'due_date'    => now()->addDays($i + 7),
                'created_at'  => now()->subDays(2),
                'updated_at'  => now()->subDays(2),
            ]);
            $this->recordHistory($taskId, null, 'todo', $pm->id, now()->subDays(2));
        }

        // ── 1 Production Bug ─────────────────────────────────────────────────
        $firstDone = DB::table('tasks')
            ->where('project_id', $pid)->where('status', 'done')
            ->whereNull('parent_id')->orderBy('id')->first();

        if ($firstDone) {
            $devHist    = DB::table('task_histories')->where('task_id', $firstDone->id)->where('to_status', 'ready_to_test')->first();
            $testerHist = DB::table('task_histories')->where('task_id', $firstDone->id)->where('to_status', 'review_approved')->first();
            $origDev    = $devHist?->changed_by    ?? $devs[0]->id;
            $origTester = $testerHist?->changed_by ?? $testers[0]->id;

            $prodTitles = $this->prodBugTitles();
            $prodBugId  = $this->insertTask([
                'code'              => $this->nextCode($code),
                'project_id'        => $pid,
                'type'              => 'bug',
                'is_production_bug' => 1,
                'linked_story_id'   => $firstDone->id,
                'title'             => $prodTitles[array_rand($prodTitles)],
                'description'       => "Lỗi production nghiêm trọng, ảnh hưởng người dùng thực. Cần xử lý khẩn trong 24h. Linked story: {$firstDone->code}.",
                'priority'          => 'critical',
                'status'            => 'in_progress',
                'created_by'        => $testers[0]->id,
                'assigned_to'       => $devs[0]->id,
                'due_date'          => now(),
                'created_at'        => now()->subDays(1),
                'updated_at'        => now()->subDays(1),
            ]);
            $this->recordHistory($prodBugId, null, 'todo', $testers[0]->id, now()->subDays(1));
            $this->recordHistory($prodBugId, 'todo', 'in_progress', $devs[0]->id, now()->subDays(1)->addHours(1));

            $this->kpiRecord($origDev, $pid, $prodBugId, -5.0,
                "Production bug: " . $this->getTaskTitle($prodBugId),
                $this->currentMonth, now()->subDays(1));
            $this->kpiRecord($origTester, $pid, $prodBugId, -5.0,
                "Production bug: " . $this->getTaskTitle($prodBugId),
                $this->currentMonth, now()->subDays(1));
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function nextCode(string $projectCode): string
    {
        $n = str_pad($this->taskIdNext, 3, '0', STR_PAD_LEFT);
        return "{$projectCode}-{$n}";
    }

    private function insertTask(array $data): int
    {
        if (isset($data['code'], $data['title'])) {
            $data['title'] = '[' . $data['code'] . '] ' . $data['title'];
        }

        $data += [
            'description'       => null,
            'parent_id'         => null,
            'is_production_bug' => 0,
            'linked_story_id'   => null,
            'estimated_hours'   => null,
            'confirmed_by'      => null,
            'started_at'        => null,
            'ready_at'          => null,
            'done_at'           => null,
            'start_date'        => null,
            'due_date'          => null,
            'updated_at'        => now(),
        ];
        $id = DB::table('tasks')->insertGetId($data);
        $this->taskIdNext++;
        return $id;
    }

    private function recordHistory(int $taskId, ?string $from, string $to, int $userId, Carbon $at): void
    {
        DB::table('task_histories')->insert([
            'task_id'     => $taskId,
            'from_status' => $from,
            'to_status'   => $to,
            'note'        => null,
            'changed_by'  => $userId,
            'created_at'  => $at,
            'updated_at'  => $at,
        ]);
    }

    private function kpiRecord(int $userId, int $projectId, int $taskId, float $points, string $reason, string $month, Carbon $at): void
    {
        DB::table('kpi_transactions')->insert([
            'user_id'      => $userId,
            'project_id'   => $projectId,
            'task_id'      => $taskId,
            'points'       => $points,
            'reason'       => $reason,
            'period_month' => $month,
            'created_at'   => $at,
            'updated_at'   => $at,
        ]);
    }

    private function getTaskTitle(int $taskId): string
    {
        return DB::table('tasks')->where('id', $taskId)->value('title') ?? "task #{$taskId}";
    }

    private function shortTitle(int $taskId): string
    {
        $title = $this->getTaskTitle($taskId);
        return mb_strlen($title) > 50 ? mb_substr($title, 0, 50) . '…' : $title;
    }
}
