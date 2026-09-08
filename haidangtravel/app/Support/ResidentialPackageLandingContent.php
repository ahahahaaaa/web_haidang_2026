<?php

namespace App\Support;

class ResidentialPackageLandingContent
{
    public static function find(string $slug): ?array
    {
        return self::pages()[$slug] ?? null;
    }

    public static function navigation(): array
    {
        return collect(self::pages())
            ->map(fn (array $page, string $slug) => [
                'slug' => $slug,
                'label' => $page['nav_label'],
                'short_label' => $page['short_label'],
                'title' => $page['title'],
            ])
            ->values()
            ->all();
    }

    public static function processSteps(): array
    {
        return [
            [
                'phase' => 'Chuẩn bị',
                'step' => '01',
                'title' => 'Tiếp nhận nhu cầu',
                'description' => 'Làm rõ quy mô nhà ở, mức đầu tư, thời điểm khởi công và cách phối hợp giữa chủ đầu tư với đội triển khai.',
                'checkpoint' => 'Khóa mục tiêu, phạm vi và đầu mối liên lạc.',
            ],
            [
                'phase' => 'Chuẩn bị',
                'step' => '02',
                'title' => 'Khảo sát và rà hồ sơ',
                'description' => 'Khảo sát hiện trạng khu đất hoặc công trình, kiểm tra hồ sơ kiến trúc, kết cấu và MEP trước khi chốt cách triển khai.',
                'checkpoint' => 'Ghi nhận rủi ro hiện trường và điểm cần xác nhận lại.',
            ],
            [
                'phase' => 'Kế hoạch',
                'step' => '03',
                'title' => 'Báo giá và tiến độ',
                'description' => 'Bóc tách phạm vi theo từng giai đoạn, xác lập timeline chính và nguyên tắc nghiệm thu, thanh toán, phát sinh.',
                'checkpoint' => 'Khóa phạm vi công việc và các mốc thanh toán.',
            ],
            [
                'phase' => 'Kế hoạch',
                'step' => '04',
                'title' => 'Chuẩn bị khởi công',
                'description' => 'Thiết lập điện nước tạm, che chắn, tim trục, cao độ và quy trình báo cáo hiện trường trước khi vào móng.',
                'checkpoint' => 'Sẵn sàng mặt bằng và biện pháp an toàn.',
            ],
            [
                'phase' => 'Thi công',
                'step' => '05',
                'title' => 'Thi công phần thô',
                'description' => 'Triển khai móng, khung, sàn, tường, chống thấm và hệ thống âm tường theo hồ sơ được chốt.',
                'checkpoint' => 'Nghiệm thu kết cấu và các hạng mục ẩn trước khi đóng hoàn thiện.',
            ],
            [
                'phase' => 'Thi công',
                'step' => '06',
                'title' => 'Tổ chức hoàn thiện',
                'description' => 'Điều phối nhân công ốp lát, trần, sơn bả, cửa, lan can, thiết bị và bảo vệ bề mặt theo đúng trình tự.',
                'checkpoint' => 'Khóa mẫu, cao độ, khe hở và chất lượng bề mặt.',
            ],
            [
                'phase' => 'Bàn giao',
                'step' => '07',
                'title' => 'Rà lỗi và nghiệm thu',
                'description' => 'Rà lỗi theo khu vực, xử lý các điểm tồn, vệ sinh công nghiệp và chốt danh mục hoàn tất trước bàn giao.',
                'checkpoint' => 'Checklist nghiệm thu được xác nhận đầy đủ.',
            ],
            [
                'phase' => 'Bàn giao',
                'step' => '08',
                'title' => 'Bàn giao và bảo hành',
                'description' => 'Bàn giao công trình theo hạng mục, hướng dẫn vận hành thiết bị và thiết lập đầu mối tiếp nhận bảo hành sau khi vào ở.',
                'checkpoint' => 'Hồ sơ, hướng dẫn sử dụng và cam kết hậu mãi rõ ràng.',
            ],
        ];
    }

    public static function comparisonRows(): array
    {
        return [
            [
                'label' => 'Mục tiêu chính',
                'standard' => 'Cân bằng giữa chi phí, tiến độ và chất lượng sử dụng thực tế.',
                'premium' => 'Nâng chuẩn hoàn thiện, độ đồng bộ vật liệu và cảm quan tổng thể của công trình.',
            ],
            [
                'label' => 'Nhóm công trình phù hợp',
                'standard' => 'Nhà phố, nhà ở gia đình, công trình cần triển khai hiệu quả và rõ phạm vi.',
                'premium' => 'Biệt thự, nhà ở cá nhân hóa cao, công trình có nhiều vật liệu hoặc chi tiết đặc thù.',
            ],
            [
                'label' => 'Cách kiểm soát phần thô',
                'standard' => 'Tập trung vào đúng kết cấu, đúng kích thước và đủ điều kiện cho hoàn thiện ổn định.',
                'premium' => 'Ngoài kỹ thuật cơ bản còn kiểm soát sâu hơn các sai số ảnh hưởng đến hoàn thiện tinh.',
            ],
            [
                'label' => 'Cách tổ chức hoàn thiện',
                'standard' => 'Theo trình tự hợp lý, bảo đảm đồng đều, sạch và đúng kỹ thuật.',
                'premium' => 'Theo trình tự chặt hơn, có mockup, mẫu duyệt và bảo vệ bề mặt nghiêm ngặt hơn.',
            ],
            [
                'label' => 'Mức độ phối hợp với chủ đầu tư',
                'standard' => 'Xác nhận ở các mốc vật liệu và phát sinh cần thiết.',
                'premium' => 'Phối hợp sát hơn ở khâu duyệt mẫu, chốt chi tiết riêng, lighting, facade và thiết bị.',
            ],
            [
                'label' => 'Nghiệm thu và bàn giao',
                'standard' => 'Ưu tiên độ đầy đủ, đúng kỹ thuật và vận hành ổn định.',
                'premium' => 'Ưu tiên thêm độ sắc nét, độ đồng bộ bề mặt và chất lượng cảm quan khi sử dụng.',
            ],
        ];
    }

    public static function diagramPath(): string
    {
        return 'diagrams/residential-construction-handover-process.svg';
    }

    protected static function pages(): array
    {
        $comparisonRows = self::comparisonRows();

        return [
            'phan-tho-hoan-thien-tieu-chuan' => [
                'variant' => 'standard',
                'nav_label' => 'Landing gói tiêu chuẩn',
                'short_label' => 'Gói tiêu chuẩn',
                'title' => 'Thi công phần thô + nhân công hoàn thiện tiêu chuẩn',
                'eyebrow' => 'GÓI NHÀ Ở TIÊU CHUẨN',
                'hero_title' => 'Gói thi công phần thô + nhân công hoàn thiện tiêu chuẩn cho nhà ở cần tiến độ rõ và chất lượng ổn định',
                'hero_description' => 'Giải pháp phù hợp cho chủ đầu tư muốn một quy trình bài bản từ chuẩn bị, phần thô, hoàn thiện đến bàn giao, nhưng vẫn giữ ngân sách ở mức hợp lý và dễ kiểm soát.',
                'hero_badges' => ['Nhà phố và nhà ở gia đình', 'Quy trình 8 bước rõ ràng', 'Kiểm soát tốt chi phí và tiến độ'],
                'hero_stats' => [
                    ['label' => 'Phù hợp', 'value' => 'Nhà phố, nhà ở gia đình'],
                    ['label' => 'Ưu tiên', 'value' => 'Hiệu quả đầu tư rõ ràng'],
                    ['label' => 'Trọng tâm', 'value' => 'Phần thô chuẩn và hoàn thiện đồng đều'],
                    ['label' => 'Quản lý', 'value' => 'Checklist, mốc nghiệm thu, báo cáo hiện trường'],
                ],
                'hero_panel' => [
                    'title' => 'Gói này hợp khi chủ đầu tư cần',
                    'items' => [
                        'Một đầu mối quản lý xuyên suốt từ phần thô đến hoàn thiện.',
                        'Công trình đúng kỹ thuật, sạch, gọn và sẵn sàng vào ở ổn định.',
                        'Phối hợp đủ chặt để hạn chế phát sinh nhưng không làm quy trình trở nên quá nặng.',
                    ],
                ],
                'fit_items' => [
                    'Nhà phố từ 2 đến 5 tầng cần quản lý thi công theo mốc rõ ràng.',
                    'Gia đình ở thực ưu tiên độ bền, tiến độ và khả năng kiểm soát ngân sách.',
                    'Công trình có mức hoàn thiện khá, không quá nhiều chi tiết cá nhân hóa phức tạp.',
                ],
                'scope_groups' => [
                    [
                        'title' => 'Phần thô được kiểm soát theo chuẩn',
                        'description' => 'Tập trung vào kết cấu, xây tô, chống thấm, hệ thống âm tường và điều kiện nền cho hoàn thiện đi ổn định.',
                        'items' => [
                            'Thi công đúng hồ sơ và đúng trình tự.',
                            'Nghiệm thu các hạng mục ẩn trước khi chuyển bước.',
                            'Giữ sai số trong ngưỡng phù hợp cho hoàn thiện đồng đều.',
                        ],
                    ],
                    [
                        'title' => 'Nhân công hoàn thiện theo trình tự hợp lý',
                        'description' => 'Đội hoàn thiện được điều phối để giảm chồng chéo, hạn chế bẩn bề mặt và giảm sửa đi sửa lại.',
                        'items' => [
                            'Kiểm tra cao độ, độ phẳng và độ vuông trước khi thi công hoàn thiện.',
                            'Sơn bả, ốp lát, trần, cửa, thiết bị đi theo thứ tự rõ.',
                            'Rà lỗi trước khi vào công tác vệ sinh và bàn giao.',
                        ],
                    ],
                    [
                        'title' => 'Phối hợp với chủ đầu tư ở đúng điểm cần thiết',
                        'description' => 'Chủ đầu tư xác nhận đúng lúc các nhóm vật liệu chính, phạm vi phát sinh và các quyết định ảnh hưởng tiến độ.',
                        'items' => [
                            'Khóa vật liệu chủ đạo trước khi triển khai diện rộng.',
                            'Phát sinh được ghi nhận rõ tác động về chi phí và thời gian.',
                            'Báo cáo hiện trường giúp quyết định nhanh nhưng vẫn kiểm soát phạm vi.',
                        ],
                    ],
                ],
                'control_cards' => [
                    [
                        'title' => 'Khóa phạm vi sớm',
                        'text' => 'Gói tiêu chuẩn hiệu quả nhất khi bản vẽ, vật liệu chính và trình tự hoàn thiện được chốt sớm.',
                    ],
                    [
                        'title' => 'Ưu tiên mốc nghiệm thu',
                        'text' => 'Những mốc như chống thấm, âm tường, cán nền, cao độ cửa cần được kiểm tra kỹ trước khi che khuất.',
                    ],
                    [
                        'title' => 'Giữ tiến độ ổn định',
                        'text' => 'Điểm mạnh của gói này là khả năng đi đều tiến độ mà vẫn duy trì chất lượng ở mức tốt và đồng nhất.',
                    ],
                ],
                'process_intro' => 'Với gói tiêu chuẩn, cùng một khung quy trình 8 bước vẫn được triển khai đầy đủ, nhưng trọng tâm nằm ở việc kiểm soát đúng kỹ thuật, đúng phạm vi và bàn giao ổn định để chủ đầu tư nhanh chóng đưa công trình vào sử dụng.',
                'comparison_title' => 'Gói tiêu chuẩn đứng ở đâu so với gói cao cấp?',
                'comparison_rows' => $comparisonRows,
                'article_sections' => [
                    [
                        'title' => 'Điểm mạnh lớn nhất của gói tiêu chuẩn',
                        'paragraphs' => [
                            'Gói này tạo ra một mặt bằng triển khai đủ chặt để công trình không bị rối trong giai đoạn phần thô và hoàn thiện, nhưng vẫn giữ được sự gọn gàng trong cách phối hợp và ra quyết định.',
                            'Nếu chủ đầu tư muốn công trình bền, đẹp, dễ ở và kiểm soát được ngân sách mà không cần mức hoàn thiện quá cầu kỳ, đây thường là phương án hiệu quả nhất.',
                        ],
                        'bullets' => [
                            'Giảm nguy cơ chậm tiến độ do phối hợp thiếu đầu mối.',
                            'Giữ chất lượng hoàn thiện ở mức đồng đều và ổn định.',
                            'Dễ đọc báo giá và dễ chốt phạm vi hơn so với các gói yêu cầu quá nhiều lớp kiểm soát sâu.',
                        ],
                    ],
                    [
                        'title' => 'Những điều chủ đầu tư nên chuẩn bị trước',
                        'paragraphs' => [
                            'Để gói tiêu chuẩn phát huy đúng hiệu quả, chủ đầu tư nên chốt sớm nhóm vật liệu chính như gạch, sơn, thiết bị vệ sinh, cửa và các thay đổi layout quan trọng trước khi công trường vào hoàn thiện diện rộng.',
                        ],
                        'bullets' => [
                            'Khóa trước các hạng mục dễ kéo chậm như cửa, đá, lan can, thiết bị đặt riêng.',
                            'Giảm đổi ý ở giai đoạn cuối để tránh ảnh hưởng dây chuyền hoàn thiện.',
                            'Luôn so sánh báo giá theo phạm vi bao gồm, không chỉ nhìn giá trên m2.',
                        ],
                    ],
                ],
                'faq' => [
                    [
                        'question' => 'Gói tiêu chuẩn có phải là gói chất lượng thấp không?',
                        'answer' => 'Không. Gói tiêu chuẩn vẫn yêu cầu quy trình thi công chuyên nghiệp, nghiệm thu từng giai đoạn và chất lượng sử dụng ổn định. Điểm khác biệt là mức độ đầu tư cho kiểm soát chi tiết và cảm quan hoàn thiện không sâu bằng gói cao cấp.',
                    ],
                    [
                        'question' => 'Khi nào nên nâng từ gói tiêu chuẩn lên gói cao cấp?',
                        'answer' => 'Khi công trình có nhiều vật liệu đặc thù, nhiều điểm giao nhau giữa các lớp hoàn thiện, nhiều chi tiết cá nhân hóa hoặc kỳ vọng rất cao về độ sắc nét và đồng bộ bề mặt.',
                    ],
                    [
                        'question' => 'Gói tiêu chuẩn phù hợp nhất với loại nhà nào?',
                        'answer' => 'Nhà phố, nhà ở gia đình và các công trình ở thực cần cân bằng tốt giữa chi phí đầu tư, tiến độ triển khai và chất lượng hoàn thiện ổn định.',
                    ],
                ],
                'cta' => [
                    'title' => 'Bạn muốn chốt nhanh phạm vi cho gói tiêu chuẩn?',
                    'description' => 'Gửi quy mô công trình, số tầng, mức hoàn thiện mong muốn và mốc bàn giao dự kiến để đội ngũ đề xuất khung triển khai phù hợp.',
                    'primary_label' => 'Nhận tư vấn gói tiêu chuẩn',
                    'secondary_label' => 'Xem gói cao cấp',
                    'secondary_slug' => 'phan-tho-hoan-thien-cao-cap',
                ],
                'meta_title' => 'Thi công phần thô + nhân công hoàn thiện tiêu chuẩn | Giải pháp nhà ở chuyên nghiệp',
                'meta_description' => 'Landing page giới thiệu gói thi công phần thô + nhân công hoàn thiện tiêu chuẩn cho nhà ở, quy trình từ chuẩn bị đến bàn giao và điểm khác biệt với gói cao cấp.',
            ],
            'phan-tho-hoan-thien-cao-cap' => [
                'variant' => 'premium',
                'nav_label' => 'Landing gói cao cấp',
                'short_label' => 'Gói cao cấp',
                'title' => 'Thi công phần thô + nhân công hoàn thiện cao cấp',
                'eyebrow' => 'GÓI NHÀ Ở CAO CẤP',
                'hero_title' => 'Gói thi công phần thô + nhân công hoàn thiện cao cấp cho công trình cần độ chỉn chu và kiểm soát chi tiết cao',
                'hero_description' => 'Giải pháp dành cho biệt thự, nhà ở cá nhân hóa cao hoặc công trình cần kiểm soát sâu hơn từ phần thô, mockup, mẫu duyệt đến cảm quan hoàn thiện khi bàn giao.',
                'hero_badges' => ['Biệt thự và nhà ở cá nhân hóa cao', 'Kiểm soát sâu từ phần thô đến hoàn thiện', 'Phù hợp công trình nhiều chi tiết đặc thù'],
                'hero_stats' => [
                    ['label' => 'Phù hợp', 'value' => 'Biệt thự, villa, nhà ở yêu cầu cao'],
                    ['label' => 'Ưu tiên', 'value' => 'Độ sắc nét và đồng bộ hoàn thiện'],
                    ['label' => 'Trọng tâm', 'value' => 'Mẫu duyệt, mockup, giao điểm kỹ thuật'],
                    ['label' => 'Quản lý', 'value' => 'Theo khu vực, vật liệu và lớp hoàn thiện'],
                ],
                'hero_panel' => [
                    'title' => 'Gói này hợp khi chủ đầu tư cần',
                    'items' => [
                        'Không gian có nhiều lớp vật liệu, lighting, facade hoặc chi tiết đặt riêng.',
                        'Độ hoàn thiện bề mặt tinh hơn mặt bằng chung và giảm tối đa sửa lỗi cuối kỳ.',
                        'Điều phối chặt giữa kiến trúc, kết cấu, MEP, nội thất và các đội hoàn thiện.',
                    ],
                ],
                'fit_items' => [
                    'Biệt thự, villa nghỉ dưỡng, nhà ở cá nhân hóa cao có nhiều chi tiết vật liệu.',
                    'Công trình yêu cầu độ đồng bộ cao giữa ngoại thất, nội thất, ánh sáng và thiết bị.',
                    'Chủ đầu tư sẵn sàng phối hợp duyệt mẫu và chốt quyết định kỹ hơn ở giai đoạn hoàn thiện.',
                ],
                'scope_groups' => [
                    [
                        'title' => 'Phần thô phải đủ chính xác cho hoàn thiện tinh',
                        'description' => 'Sai số ở phần thô có thể phá vỡ chất lượng hoàn thiện cao cấp, nên công tác kiểm soát kích thước, cao độ và các vị trí chờ được làm sâu hơn.',
                        'items' => [
                            'Rà soát kỹ hơn các trục, cao độ và mặt phẳng chuẩn.',
                            'Kiểm tra sớm các điểm liên quan đến đá, gỗ, hệ cửa, lighting và thiết bị âm.',
                            'Phối hợp MEP chặt hơn để tránh đục sửa khi vào hoàn thiện.',
                        ],
                    ],
                    [
                        'title' => 'Hoàn thiện theo mẫu và theo lớp kiểm soát',
                        'description' => 'Gói cao cấp không chỉ làm đúng vật liệu, mà còn làm đúng cảm giác bề mặt, đúng chuyển tiếp giữa các lớp hoàn thiện và đúng chuẩn lắp đặt.',
                        'items' => [
                            'Mockup trước với các hạng mục nhạy cảm như đá, vách, cửa, trần và lighting.',
                            'Giữ đồng bộ batch màu, bề mặt và quy cách lắp đặt.',
                            'Bảo vệ bề mặt sớm để tránh hư hao trong quá trình thi công chồng lớp.',
                        ],
                    ],
                    [
                        'title' => 'Nghiệm thu sâu hơn trước khi bàn giao',
                        'description' => 'Thay vì chỉ kiểm tra đủ và đúng kỹ thuật, công trình còn được rà soát thêm về cảm quan, độ sắc nét và tính đồng nhất của từng khu vực.',
                        'items' => [
                            'Rà lỗi theo không gian và theo vật liệu.',
                            'Xử lý kỹ các giao tuyến, khe hở, điểm bo và điểm nối.',
                            'Bàn giao theo checklist chi tiết hơn, thuận tiện cho bảo trì lâu dài.',
                        ],
                    ],
                ],
                'control_cards' => [
                    [
                        'title' => 'Mockup trước khi làm đại trà',
                        'text' => 'Các điểm như bề mặt đá, vách trang trí, hệ cửa, trần đèn và chi tiết bo nối nên có bước duyệt mẫu rõ ràng.',
                    ],
                    [
                        'title' => 'Kiểm soát sâu giao điểm kỹ thuật',
                        'text' => 'Nhiều lỗi cảm quan ở công trình cao cấp không đến từ vật liệu, mà đến từ việc xung đột giữa kiến trúc, MEP và thứ tự thi công.',
                    ],
                    [
                        'title' => 'Bảo vệ bề mặt là bắt buộc',
                        'text' => 'Vật liệu càng cao cấp thì chi phí sửa lỗi càng lớn, nên phương án che chắn và bàn giao theo khu vực cần được chuẩn hóa sớm.',
                    ],
                ],
                'process_intro' => 'Gói cao cấp vẫn đi theo cùng khung quy trình chuẩn từ chuẩn bị đến bàn giao, nhưng mỗi giai đoạn được tăng thêm lớp kiểm soát cho vật liệu, chi tiết giao nhau, mẫu duyệt và tiêu chuẩn cảm quan khi sử dụng thực tế.',
                'comparison_title' => 'Gói cao cấp khác biệt ở đâu so với gói tiêu chuẩn?',
                'comparison_rows' => $comparisonRows,
                'article_sections' => [
                    [
                        'title' => 'Điều làm gói cao cấp trở nên đáng giá',
                        'paragraphs' => [
                            'Giá trị của gói cao cấp không chỉ nằm ở vật liệu hay hình ảnh cuối cùng, mà ở khả năng giảm lỗi tích lũy từ phần thô đến hoàn thiện để công trình đạt cảm giác chỉn chu khi vào ở.',
                            'Những công trình nhiều chi tiết đẹp thường không thất bại ở ý tưởng, mà thất bại ở phối hợp và mức độ kiểm soát khi thi công thực tế.',
                        ],
                        'bullets' => [
                            'Giảm đục sửa và thay đổi muộn ở giai đoạn cuối.',
                            'Tăng độ đồng bộ giữa facade, nội thất, lighting và thiết bị.',
                            'Giữ trải nghiệm cảm quan ổn định hơn khi bàn giao thực tế.',
                        ],
                    ],
                    [
                        'title' => 'Chủ đầu tư cần chuẩn bị điều gì khi chọn gói này',
                        'paragraphs' => [
                            'Gói cao cấp yêu cầu chủ đầu tư ra quyết định sớm và nhất quán hơn ở vật liệu, màu sắc, thiết bị, chi tiết riêng và mức độ hoàn thiện mục tiêu. Đây là điều kiện để công trường không bị kéo dài hoặc phải sửa lặp ở giai đoạn cuối.',
                        ],
                        'bullets' => [
                            'Duyệt mẫu vật liệu và chi tiết giao nhau trước khi làm đại trà.',
                            'Chốt sớm các hạng mục đặt riêng như cửa, đá, đèn, thiết bị và phụ kiện.',
                            'Ưu tiên họp ngắn nhưng quyết định dứt khoát ở các mốc then chốt.',
                        ],
                    ],
                ],
                'faq' => [
                    [
                        'question' => 'Gói cao cấp có chỉ dành cho biệt thự lớn không?',
                        'answer' => 'Không. Gói cao cấp phù hợp cho bất kỳ nhà ở nào có yêu cầu cao về chi tiết hoàn thiện, nhiều vật liệu đặc thù hoặc kỳ vọng đồng bộ mạnh giữa kiến trúc, nội thất và thiết bị.',
                    ],
                    [
                        'question' => 'Điều gì thường làm công trình cao cấp bị lỗi nhiều nhất?',
                        'answer' => 'Thường là sai số phần thô, thiếu mockup, duyệt mẫu muộn, xung đột giữa MEP và hoàn thiện hoặc thay đổi quyết định ở giai đoạn cuối.',
                    ],
                    [
                        'question' => 'Nếu ngân sách có giới hạn, có thể chọn cao cấp một phần không?',
                        'answer' => 'Có thể. Nhiều chủ đầu tư chọn giữ khung triển khai tiêu chuẩn cho phần lớn công trình nhưng nâng chuẩn kiểm soát ở các khu vực trọng điểm như mặt tiền, phòng khách, bếp, thang hoặc phòng ngủ master.',
                    ],
                ],
                'cta' => [
                    'title' => 'Bạn muốn rà soát xem công trình có nên đi theo gói cao cấp không?',
                    'description' => 'Gửi nhu cầu, hình ảnh tham khảo, nhóm vật liệu dự kiến và mức hoàn thiện kỳ vọng để đội ngũ gợi ý cách khóa phạm vi phù hợp.',
                    'primary_label' => 'Nhận tư vấn gói cao cấp',
                    'secondary_label' => 'Xem trang so sánh',
                    'secondary_slug' => 'so-sanh-tieu-chuan-va-cao-cap',
                ],
                'meta_title' => 'Thi công phần thô + nhân công hoàn thiện cao cấp | Giải pháp nhà ở kiểm soát sâu',
                'meta_description' => 'Landing page giới thiệu gói thi công phần thô + nhân công hoàn thiện cao cấp cho nhà ở, quy trình triển khai, điểm mạnh và khác biệt với gói tiêu chuẩn.',
            ],
            'so-sanh-tieu-chuan-va-cao-cap' => [
                'variant' => 'comparison',
                'nav_label' => 'Landing so sánh 2 gói',
                'short_label' => 'So sánh 2 gói',
                'title' => 'So sánh gói thi công phần thô + nhân công hoàn thiện tiêu chuẩn và cao cấp',
                'eyebrow' => 'TRANG SO SÁNH 2 GÓI',
                'hero_title' => 'So sánh rõ điểm khác biệt giữa gói thi công phần thô + nhân công hoàn thiện tiêu chuẩn và cao cấp',
                'hero_description' => 'Trang này giúp chủ đầu tư nhìn nhanh sự khác nhau về mục tiêu, mức kiểm soát, cách tổ chức hoàn thiện và tiêu chuẩn bàn giao của hai gói, từ đó chọn đúng hướng triển khai cho công trình nhà ở.',
                'hero_badges' => ['1 bảng so sánh trọng tâm', '1 khung quy trình từ chuẩn bị đến bàn giao', '3 tình huống chọn gói thực tế'],
                'hero_stats' => [
                    ['label' => 'Gói được so sánh', 'value' => '2 gói nhà ở chủ lực'],
                    ['label' => 'Khung quy trình', 'value' => '8 bước từ chuẩn bị đến bàn giao'],
                    ['label' => 'Trọng tâm', 'value' => 'Phạm vi, kiểm soát, hoàn thiện, bàn giao'],
                    ['label' => 'Mục tiêu', 'value' => 'Giúp chọn gói đúng thay vì chọn theo cảm tính'],
                ],
                'hero_dual_panels' => [
                    [
                        'title' => 'Gói tiêu chuẩn',
                        'accent' => 'standard',
                        'items' => [
                            'Phù hợp khi cần cân bằng tốt giữa chi phí, tiến độ và chất lượng sử dụng.',
                            'Đủ chặt để phần thô và hoàn thiện đi ổn định.',
                            'Dễ triển khai cho nhà phố và công trình ở thực phổ biến.',
                        ],
                    ],
                    [
                        'title' => 'Gói cao cấp',
                        'accent' => 'premium',
                        'items' => [
                            'Phù hợp khi công trình cần độ sắc nét và mức kiểm soát chi tiết cao hơn.',
                            'Tăng lớp quản lý ở vật liệu, mẫu duyệt, mockup và giao điểm kỹ thuật.',
                            'Hiệu quả cho biệt thự hoặc công trình cá nhân hóa cao.',
                        ],
                    ],
                ],
                'fit_items' => [
                    'Chọn gói tiêu chuẩn nếu ưu tiên hiệu quả đầu tư, tiến độ rõ và mức hoàn thiện phù hợp ở thực.',
                    'Chọn gói cao cấp nếu ưu tiên cảm quan bề mặt, độ tinh chi tiết và mức đồng bộ cao giữa nhiều lớp vật liệu.',
                    'Không nên so sánh hai gói chỉ bằng giá trên m2; cần nhìn đúng phạm vi và chiều sâu kiểm soát.',
                ],
                'scope_groups' => [
                    [
                        'title' => 'Điểm giống nhau của cả hai gói',
                        'description' => 'Cả hai đều phải đi qua một quy trình chuyên nghiệp từ tiếp nhận nhu cầu đến bàn giao và bảo hành.',
                        'items' => [
                            'Khảo sát, rà hồ sơ, báo giá và lập tiến độ.',
                            'Thi công phần thô, tổ chức hoàn thiện và nghiệm thu.',
                            'Bàn giao công trình có checklist và đầu mối hậu mãi.',
                        ],
                    ],
                    [
                        'title' => 'Điểm khác nhau lớn nhất',
                        'description' => 'Khác biệt chính không nằm ở việc có làm đúng quy trình hay không, mà nằm ở độ sâu của quản lý và chuẩn hoàn thiện mục tiêu.',
                        'items' => [
                            'Gói tiêu chuẩn ưu tiên ổn định, rõ phạm vi, tối ưu hiệu quả triển khai.',
                            'Gói cao cấp ưu tiên tăng lớp kiểm soát để đạt hoàn thiện tinh và đồng bộ hơn.',
                            'Công trình càng nhiều chi tiết thì khác biệt giữa hai gói càng rõ.',
                        ],
                    ],
                    [
                        'title' => 'Cách đọc báo giá để so đúng',
                        'description' => 'Một báo giá rẻ hơn chưa chắc hiệu quả hơn nếu phạm vi kiểm soát hoặc hạng mục bao gồm bị mỏng đi đáng kể.',
                        'items' => [
                            'So theo danh mục công việc và cách nghiệm thu.',
                            'So theo mức phối hợp vật liệu, mẫu duyệt và phát sinh.',
                            'So theo chuẩn bàn giao cuối cùng, không chỉ so theo thi công phần thô.',
                        ],
                    ],
                ],
                'control_cards' => [
                    [
                        'title' => 'Nếu muốn dễ kiểm soát',
                        'text' => 'Gói tiêu chuẩn thường là lựa chọn hợp lý cho nhà ở cần đi nhanh, gọn, rõ và đủ đẹp để sử dụng lâu dài.',
                    ],
                    [
                        'title' => 'Nếu muốn độ chỉn chu cao',
                        'text' => 'Gói cao cấp phù hợp khi giá trị công trình nằm nhiều ở vật liệu, chi tiết và cảm quan sử dụng sau khi bàn giao.',
                    ],
                    [
                        'title' => 'Nếu còn phân vân',
                        'text' => 'Có thể chọn giải pháp lai: giữ khung tiêu chuẩn cho toàn công trình nhưng nâng chuẩn kiểm soát ở các khu vực trọng điểm.',
                    ],
                ],
                'process_intro' => 'Dù chọn gói nào, một công trình nhà ở vẫn nên đi qua cùng một khung quy trình rõ ràng từ chuẩn bị đến bàn giao. Sự khác nhau nằm ở mức độ kiểm soát và tiêu chuẩn đầu ra tại từng bước.',
                'comparison_title' => 'Bảng so sánh điểm khác biệt trọng tâm',
                'comparison_rows' => $comparisonRows,
                'article_sections' => [
                    [
                        'title' => 'Sự khác biệt không chỉ nằm ở đơn giá',
                        'paragraphs' => [
                            'Nhiều chủ đầu tư so gói tiêu chuẩn và gói cao cấp bằng giá trên m2, nhưng thực tế chênh lệch thường đến từ số lớp quản lý, mức độ kiểm soát phần thô phục vụ hoàn thiện, khối lượng mẫu duyệt và chuẩn bàn giao mục tiêu.',
                            'Vì vậy, muốn chọn đúng gói thì phải nhìn vào cách công trình sẽ được quản lý trong suốt vòng đời thi công, chứ không chỉ nhìn con số đầu vào.',
                        ],
                        'bullets' => [
                            'Gói tiêu chuẩn tối ưu hiệu quả triển khai.',
                            'Gói cao cấp tối ưu chất lượng cảm quan và độ tinh của chi tiết.',
                            'Cùng một diện tích nhưng công trình càng nhiều chi tiết thì nhu cầu kiểm soát sâu càng lớn.',
                        ],
                    ],
                    [
                        'title' => 'Ba tình huống ra quyết định nhanh',
                        'paragraphs' => [
                            'Nếu bạn đang xây nhà phố ở thực, muốn rõ tiến độ, đúng kỹ thuật và giữ ngân sách gọn, hãy nghiêng về gói tiêu chuẩn.',
                            'Nếu công trình là biệt thự, có nhiều vật liệu mặt đứng, hệ cửa, lighting, đá hoặc nội thất đồng bộ, hãy nghiêng về gói cao cấp.',
                            'Nếu công trình có cả khu vực phổ thông lẫn khu vực cần điểm nhấn mạnh, có thể tách mức kiểm soát theo khu vực để đầu tư hiệu quả hơn.',
                        ],
                        'bullets' => [
                            'Ưu tiên ở thực phổ biến: tiêu chuẩn.',
                            'Ưu tiên chi tiết và cảm quan cao: cao cấp.',
                            'Ưu tiên cân bằng linh hoạt theo khu vực: giải pháp lai hoặc nâng chuẩn cục bộ.',
                        ],
                    ],
                ],
                'faq' => [
                    [
                        'question' => 'Hai gói có cùng quy trình xây dựng không?',
                        'answer' => 'Có cùng khung quy trình chính từ chuẩn bị, thi công phần thô, hoàn thiện đến bàn giao. Khác biệt nằm ở độ sâu kiểm soát và tiêu chuẩn chất lượng đầu ra trong từng bước.',
                    ],
                    [
                        'question' => 'Vì sao có công trình diện tích không lớn nhưng vẫn nên dùng gói cao cấp?',
                        'answer' => 'Vì mức độ phù hợp không phụ thuộc hoàn toàn vào diện tích. Một công trình nhỏ nhưng nhiều vật liệu đặc thù, nhiều lighting, nhiều chi tiết mặt đứng hoặc yêu cầu hoàn thiện tinh vẫn nên đi theo gói cao cấp.',
                    ],
                    [
                        'question' => 'Có thể chuyển từ tiêu chuẩn sang cao cấp giữa chừng không?',
                        'answer' => 'Có thể nhưng không phải lúc nào cũng hiệu quả. Càng chuyển muộn, đặc biệt khi đã qua phần thô hoặc đã đặt vật liệu, chi phí sửa điều kiện nền và điều phối lại sẽ càng lớn.',
                    ],
                ],
                'cta' => [
                    'title' => 'Bạn muốn đội ngũ đề xuất gói phù hợp cho chính công trình của mình?',
                    'description' => 'Chia sẻ loại nhà, diện tích sàn, mức hoàn thiện kỳ vọng và những khu vực cần đầu tư trọng điểm để nhận định hướng triển khai phù hợp.',
                    'primary_label' => 'Nhận tư vấn chọn gói',
                    'secondary_label' => 'Xem gói tiêu chuẩn',
                    'secondary_slug' => 'phan-tho-hoan-thien-tieu-chuan',
                ],
                'meta_title' => 'So sánh gói thi công phần thô + nhân công hoàn thiện tiêu chuẩn và cao cấp',
                'meta_description' => 'Landing page so sánh rõ điểm khác biệt giữa gói thi công phần thô + nhân công hoàn thiện tiêu chuẩn và cao cấp cho công trình nhà ở.',
            ],
        ];
    }
}
