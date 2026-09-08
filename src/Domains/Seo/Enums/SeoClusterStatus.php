<?php
namespace Src\Domains\Seo\Enums;
enum SeoClusterStatus: string { case Draft='draft'; case Approved='approved'; case Generating='generating'; case Completed='completed'; case Paused='paused'; }
