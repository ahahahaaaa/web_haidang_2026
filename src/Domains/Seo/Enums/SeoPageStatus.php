<?php
namespace Src\Domains\Seo\Enums;
enum SeoPageStatus: string { case Draft='draft'; case Generated='generated'; case QaFailed='qa_failed'; case PendingReview='pending_review'; case Approved='approved'; case Published='published'; case Archived='archived'; }
