<?php
if (!defined('ABSPATH')) { exit; }

/**
 * ساخت خودکار محصولات پایه (لنت، سپر، چراغ، آینه و ...) برای همه‌ی مدل‌های vehicles-seed.php.
 * - هر محصول به مدل خودش (برند ← مدل) وصل می‌شه و توی دسته‌ی قطعه‌اش قرار می‌گیره.
 * - پیوند یکتا انگلیسیه، مثل /product/chery-mvm-x22-front-brake-pads/
 * - عنوان، توضیح کامل، توضیح کوتاه و فیلدهای سئوی Rank Math پر می‌شن.
 * - قیمت خالیه (۰ = «برای قیمت تماس بگیرید») تا خودت از پیشخوان قیمت بذاری.
 * - عکس نداره؛ تا عکس بذاری، آیکون همون قطعه نمایش داده می‌شه.
 * چون حدود هزار محصوله، دسته‌دسته (هر بار باز کردن پیشخوان ۴۰ تا) ساخته می‌شن.
 * محصولی که از قبل با همون پیوند وجود داره دوباره ساخته نمی‌شه.
 */

define('AP_PARTS_SEED_VERSION', '8');
define('AP_PARTS_SEED_BATCH', 40);

/* [اسلاگ قطعه, نام فارسی, اسلاگ دسته, نام دسته, آیکون, توضیح کوتاه, پاراگراف توضیح] */
function ap_parts_seed_list() {
    return [
        ['front-brake-pads', 'لنت ترمز جلو', 'brake-pads', 'لنت ترمز', 'brake',
            'لنت ترمز جلو با ترکیب کم‌صدا و کم‌گرد، مناسب رانندگی شهری و جاده‌ای.',
            'لنت ترمز جلو مهم‌ترین قطعه‌ی سیستم ترمز است و بیشترین نیروی توقف خودرو را تأمین می‌کند. این لنت با ابعاد و محل نصب دقیقاً منطبق با کالیپر جلوی خودرو طراحی شده، روی دیسک صدای جیرجیر ایجاد نمی‌کند و گرد ترمز کمتری روی رینگ می‌نشاند. ضخامت لنت را در هر سرویس دوره‌ای بررسی کنید و هنگام تعویض، لنت هر دو چرخ جلو را با هم عوض کنید.'],
        ['rear-brake-pads', 'لنت ترمز عقب', 'brake-pads', 'لنت ترمز', 'brake',
            'لنت ترمز عقب با عمر کاری بالا و عملکرد یکنواخت در دمای بالا.',
            'لنت ترمز عقب در کنار لنت جلو، تعادل خودرو را هنگام ترمزگیری حفظ می‌کند و در خودروهای مجهز به ترمز دستی برقی نیز نقش دارد. این قطعه با ابعاد استاندارد کالیپر عقب ساخته شده و پس از آب‌بندی اولیه، ترمزگیری نرم و بدون لرزش ارائه می‌دهد. پیشنهاد می‌شود لنت دو چرخ عقب همزمان تعویض و دیسک‌ها نیز بررسی شوند.'],
        ['front-bumper', 'سپر جلو', 'front-bumper', 'سپر جلو', 'bumper',
            'سپر جلو با قالب دقیق و محل نصب یکسان با قطعه‌ی اصلی.',
            'سپر جلو علاوه بر زیبایی ظاهری، اولین بخش جذب ضربه در برخوردهای سبک است. این سپر از پلاستیک مقاوم و انعطاف‌پذیر ساخته شده، جای پیچ‌ها، گیره‌ها، مه‌شکن و سنسورها در آن مطابق قطعه‌ی اصلی است و بدون تغییر روی خودرو نصب می‌شود. سپر به‌صورت خام (آماده‌ی رنگ) عرضه می‌شود؛ پیش از نصب آن را هم‌رنگ بدنه کنید.'],
        ['rear-bumper', 'سپر عقب', 'rear-bumper', 'سپر عقب', 'bumper',
            'سپر عقب آماده‌ی رنگ با محل دقیق سنسور پارک و پلاک.',
            'سپر عقب از بدنه و صندوق عقب در برابر ضربه‌های پارک و برخوردهای سبک محافظت می‌کند. قالب این قطعه مطابق سپر اصلی است و محل سنسورهای پارک، چراغ مه‌شکن عقب و پلاک در آن پیش‌بینی شده است. سپر به‌صورت خام و آماده‌ی رنگ عرضه می‌شود.'],
        ['headlight', 'چراغ جلو', 'headlight', 'چراغ جلو', 'light',
            'چراغ جلو با شیشه‌ی شفاف و مقاوم در برابر زردشدن، سوکت یکسان با قطعه‌ی اصلی.',
            'چراغ جلو برای دید کافی در شب و دیده‌شدن خودرو در جاده ضروری است. این چراغ با شیشه‌ی پلی‌کربنات مقاوم در برابر خط و زردشدن، رفلکتور دقیق و سوکت برق یکسان با قطعه‌ی اصلی ساخته شده و مستقیم جایگزین چراغ قبلی می‌شود. هنگام سفارش، سمت چراغ (راست یا چپ) را مشخص کنید و پس از نصب، تنظیم نور چراغ را انجام دهید.'],
        ['taillight', 'چراغ عقب', 'taillight', 'چراغ عقب', 'light',
            'چراغ خطر عقب با آب‌بندی کامل و نور یکنواخت.',
            'چراغ عقب شامل چراغ ترمز، راهنما و چراغ کوچک است و نقش مهمی در ایمنی رانندگی دارد. این قطعه با واشر آب‌بندی کامل ساخته شده تا رطوبت و گردوغبار به داخل آن نفوذ نکند و ابعاد و سوکت آن کاملاً با قطعه‌ی اصلی یکسان است. هنگام سفارش، سمت چراغ را مشخص کنید.'],
        ['side-mirror', 'آینه بغل', 'side-mirror', 'آینه بغل', 'mirror',
            'آینه بغل کامل با قاب، شیشه و موتور برقی (در مدل‌های برقی).',
            'آینه بغل برای دید عقب و کنار خودرو و تغییر لاین ایمن ضروری است. این آینه به‌صورت کامل (قاب، شیشه و پایه) عرضه می‌شود و در مدل‌های مجهز، موتور تنظیم برقی، گرم‌کن و راهنمای روی آینه را نیز دارد. سوکت برق و محل پیچ‌ها مطابق قطعه‌ی اصلی است. هنگام سفارش، سمت آینه را مشخص کنید.'],
        ['front-fender', 'گلگیر جلو', 'fender', 'گلگیر', 'fender',
            'گلگیر جلو فلزی با ضخامت استاندارد و پوشش ضدزنگ.',
            'گلگیر جلو بخشی از بدنه است که اطراف چرخ جلو را می‌پوشاند و از پاشیدن گل و سنگ به موتور و بدنه جلوگیری می‌کند. این قطعه از ورق فولادی با ضخامت استاندارد و پوشش ضدزنگ (آستر) ساخته شده و فاصله‌ی درزها پس از نصب با درب موتور و درب جلو یکسان می‌ماند. قطعه آماده‌ی رنگ عرضه می‌شود؛ سمت گلگیر را هنگام سفارش مشخص کنید.'],
        ['hood', 'درب موتور (کاپوت)', 'hood', 'درب موتور', 'hood',
            'درب موتور فلزی آماده‌ی رنگ با محل دقیق لولا و قفل.',
            'درب موتور (کاپوت) از موتور و قطعات محفظه‌ی موتور محافظت می‌کند و در شکل ظاهری جلوی خودرو نقش اصلی دارد. این قطعه از ورق فولادی با تقویت‌کننده‌های داخلی ساخته شده و محل لولا، قفل و جک‌های نگه‌دارنده دقیقاً مطابق قطعه‌ی اصلی است. درب موتور آستر خورده و آماده‌ی رنگ عرضه می‌شود.'],
        ['grille', 'جلو پنجره', 'grille', 'جلو پنجره', 'bumper',
            'جلو پنجره با طرح و آرم‌جای یکسان با قطعه‌ی اصلی.',
            'جلو پنجره هوای لازم برای خنک‌کاری رادیاتور و موتور را تأمین می‌کند و هویت ظاهری جلوی خودرو را شکل می‌دهد. این قطعه از پلاستیک ABS مقاوم ساخته شده، طرح، رنگ و محل نصب آرم آن مطابق قطعه‌ی اصلی است و با گیره‌ها و پیچ‌های استاندارد نصب می‌شود.'],
        ['radiator', 'رادیاتور آب', 'radiator', 'رادیاتور', 'radiator',
            'رادیاتور آب آلومینیومی با انتقال حرارت بالا و اتصالات یکسان با قطعه‌ی اصلی.',
            'رادیاتور آب دمای موتور را در محدوده‌ی مجاز نگه می‌دارد و از جوش آوردن و آسیب جدی به موتور جلوگیری می‌کند. این رادیاتور با هسته‌ی آلومینیومی و مخازن پلاستیکی مقاوم ساخته شده و ابعاد، محل شیلنگ‌ها و پایه‌های نصب آن کاملاً با قطعه‌ی اصلی یکسان است. پس از نصب، سیستم خنک‌کاری را با ضدیخ مناسب پر و هواگیری کنید.'],
        ['oil-filter', 'فیلتر روغن', 'engine-parts', 'قطعات موتوری', 'filter',
            'فیلتر روغن با کاغذ فیلتر باکیفیت و سوپاپ ضدبرگشت.',
            'فیلتر روغن ذرات فلزی و آلودگی‌های روغن موتور را جدا می‌کند و عمر موتور را بالا می‌برد. این فیلتر با کاغذ فیلتراسیون باکیفیت، واشر لاستیکی مقاوم در برابر حرارت و سوپاپ ضدبرگشت ساخته شده تا هنگام روشن‌شدن موتور روغن فوراً به قطعات برسد. فیلتر روغن را در هر بار تعویض روغن موتور عوض کنید.'],
        ['air-filter', 'فیلتر هوا', 'engine-parts', 'قطعات موتوری', 'filter',
            'فیلتر هوای موتور با جذب بالای گردوغبار و افت فشار کم.',
            'فیلتر هوا از ورود گردوغبار به موتور جلوگیری می‌کند و تأثیر مستقیمی بر مصرف سوخت و قدرت موتور دارد. این فیلتر با ابعاد دقیق محفظه‌ی فیلتر هوا و کاغذ چندلایه ساخته شده که ذرات ریز را جذب می‌کند و جریان هوای کافی به موتور می‌رساند. پیشنهاد می‌شود هر ۱۰ تا ۱۵ هزار کیلومتر، و در مناطق پرگردوغبار زودتر، تعویض شود.'],
        ['spark-plugs', 'شمع موتور (دست کامل)', 'electric-parts', 'قطعات برقی', 'electric',
            'دست کامل شمع موتور با جرقه‌ی قوی و عمر کاری بالا.',
            'شمع موتور جرقه‌ی لازم برای احتراق سوخت را ایجاد می‌کند و شمع فرسوده باعث بدروشن‌شدن، لرزش و افزایش مصرف سوخت می‌شود. این بسته شامل دست کامل شمع با دهانه‌ی استاندارد و الکترود مقاوم است. تعداد شمع‌ها مطابق تعداد سیلندر خودرو است؛ شمع‌ها را با گشتاور استاندارد ببندید.'],
        ['front-brake-discs', 'دیسک ترمز جلو', 'brake-discs', 'دیسک ترمز', 'disc',
            'دیسک ترمز جلو با آلیاژ مقاوم در برابر حرارت و تاب‌برداشتن.',
            'دیسک ترمز جلو همراه با لنت، نیروی توقف خودرو را تأمین می‌کند. این دیسک با ابعاد، ضخامت و محل پیچ‌های دقیقاً منطبق با توپی چرخ ساخته شده و آلیاژ آن در برابر حرارتِ ترمزهای پیاپی تاب برنمی‌دارد. لرزش فرمان هنگام ترمز نشانه‌ی تاب‌داشتن دیسک است؛ دیسک‌های هر محور را با هم تعویض کنید.'],
        ['front-shock-absorbers', 'کمک فنر جلو', 'suspension', 'جلوبندی و تعلیق', 'disc',
            'کمک فنر جلو گازی با عملکرد نرم و کنترل بهتر خودرو در دست‌انداز.',
            'کمک فنر جلو نوسانات فنر را کنترل می‌کند و چسبندگی لاستیک به جاده، پایداری در پیچ و راحتی سرنشینان به آن وابسته است. این کمک فنر از نوع گازی است، طول و محل نصب آن مطابق قطعه‌ی اصلی است و در دست‌اندازها خودرو را نرم و بدون ضربه نگه می‌دارد. کمک فنرهای هر محور را با هم تعویض کنید.'],
        ['rear-shock-absorbers', 'کمک فنر عقب', 'suspension', 'جلوبندی و تعلیق', 'disc',
            'کمک فنر عقب با عمر کاری بالا و عملکرد یکنواخت زیر بار.',
            'کمک فنر عقب در حفظ تعادل خودرو هنگام ترمز و حمل بار نقش مهمی دارد. این قطعه با ابعاد و محل نصب یکسان با قطعه‌ی اصلی ساخته شده و حتی با خودروی پربار نیز عملکرد یکنواخت دارد. کمک فنرهای هر محور را با هم تعویض کنید.'],
        ['front-control-arm', 'طبق جلو', 'suspension', 'جلوبندی و تعلیق', 'disc',
            'طبق جلو کامل با سیبک و بوش‌های نصب‌شده.',
            'طبق جلو چرخ را به بدنه وصل می‌کند و زوایای فرمان و تعادل خودرو را حفظ می‌کند. این طبق به‌صورت کامل همراه با سیبک و بوش‌های لاستیکی عرضه می‌شود و محل نصب آن دقیقاً مطابق قطعه‌ی اصلی است. صدای تق‌تق در دست‌انداز و کشیدن خودرو به یک سمت از نشانه‌های خرابی طبق است؛ پس از تعویض، تنظیم فرمان (تنظیم باد) انجام دهید. سمت طبق را هنگام سفارش مشخص کنید.'],
        ['fog-light', 'چراغ مه‌شکن', 'fog-light', 'چراغ مه‌شکن', 'light',
            'چراغ مه‌شکن جلو با نور متمرکز و آب‌بندی کامل.',
            'چراغ مه‌شکن در هوای مه‌آلود، بارانی و گردوغباری دید نزدیک جاده را بهتر می‌کند. این چراغ با محل نصب و سوکت یکسان با قطعه‌ی اصلی ساخته شده و آب‌بندی کامل دارد. هنگام سفارش، سمت چراغ را مشخص کنید.'],
        ['ac-condenser', 'رادیاتور کولر', 'radiator', 'رادیاتور', 'radiator',
            'رادیاتور کولر (کندانسور) آلومینیومی با اتصالات یکسان با قطعه‌ی اصلی.',
            'رادیاتور کولر (کندانسور) گرمای گاز کولر را دفع می‌کند و بدون آن، کولر خودرو خنک نمی‌کند. این قطعه با هسته‌ی آلومینیومی و ابعاد و محل اتصالات دقیقاً مطابق قطعه‌ی اصلی ساخته شده است. پس از نصب، سیستم کولر باید وکیوم و شارژ گاز شود.'],
        ['cabin-filter', 'فیلتر کابین', 'engine-parts', 'قطعات موتوری', 'filter',
            'فیلتر هوای کابین برای هوای تمیزتر داخل خودرو و کارکرد بهتر کولر.',
            'فیلتر کابین گردوغبار، گرده و آلودگی هوای ورودی به داخل خودرو را می‌گیرد و جریان هوای کولر و بخاری را حفظ می‌کند. این فیلتر با ابعاد دقیق محفظه‌ی فیلتر ساخته شده است. پیشنهاد می‌شود هر ۱۰ تا ۱۵ هزار کیلومتر تعویض شود؛ بوی نامطبوع و ضعیف‌شدن باد کولر نشانه‌ی کثیف‌شدن آن است.'],
        ['car-battery', 'باتری خودرو', 'electric-parts', 'قطعات برقی', 'electric',
            'باتری ۱۲ ولت با آمپر مناسب و استارت مطمئن در هوای سرد.',
            'باتری خودرو برق لازم برای روشن‌شدن و سیستم‌های برقی را تأمین می‌کند. این باتری با آمپر، ابعاد و جهت قطب‌های مناسب این خودرو انتخاب شده و در هوای سرد هم استارت مطمئن می‌دهد. در خودروهای برقی و هیبرید، این همان باتری ۱۲ ولت کمکی است. هنگام سفارش، آمپر باتری فعلی را بررسی کنید.'],
        ['wiper-blades', 'تیغه برف‌پاک‌کن', 'body-parts', 'قطعات بدنه', 'hood',
            'جفت تیغه برف‌پاک‌کن جلو با لاستیک نرم و بدون صدا.',
            'تیغه برف‌پاک‌کن برای دید کافی در باران و برف ضروری است. این بسته شامل جفت تیغه‌ی جلو با طول مناسب این خودرو و لاستیک نرمی است که شیشه را بدون خط و صدا تمیز می‌کند. تیغه‌ها را سالی یک‌بار یا با دیدن خط روی شیشه تعویض کنید.'],
        ['fuel-filter', 'فیلتر بنزین', 'engine-parts', 'قطعات موتوری', 'filter',
            'فیلتر بنزین با فیلتراسیون دقیق برای محافظت از انژکتورها.',
            'فیلتر بنزین ذرات و آلودگی‌های سوخت را پیش از رسیدن به انژکتورها می‌گیرد. فیلتر گرفته باعث کاهش قدرت، بدروشن‌شدن و فشار آوردن به پمپ بنزین می‌شود. این فیلتر با اتصالات یکسان با قطعه‌ی اصلی ساخته شده است.'],
        ['timing-belt-kit', 'کیت تسمه تایم', 'engine-parts', 'قطعات موتوری', 'engine',
            'کیت کامل تسمه تایم شامل تسمه، بلبرینگ هرزگرد و تسمه‌سفت‌کن.',
            'تسمه تایم حرکت میل‌لنگ و میل‌سوپاپ را هماهنگ می‌کند و پاره‌شدن آن می‌تواند به موتور آسیب جدی بزند. این کیت شامل تسمه، بلبرینگ هرزگرد و تسمه‌سفت‌کن است تا همه با هم تعویض شوند. زمان تعویض را طبق دفترچه‌ی خودرو رعایت کنید. (در موتورهای زنجیر تایم، زنجیر و متعلقاتش ارسال می‌شود.)'],
        ['water-pump', 'واتر پمپ', 'engine-parts', 'قطعات موتوری', 'engine',
            'واتر پمپ با پروانه‌ی مقاوم و آب‌بندی بدون نشتی.',
            'واتر پمپ مایع خنک‌کننده را در موتور و رادیاتور به گردش درمی‌آورد. نشتی آب از زیر موتور و بالا رفتن دمای موتور از نشانه‌های خرابی واتر پمپ است. این قطعه با واشر و ابعاد یکسان با قطعه‌ی اصلی ساخته شده و معمولاً همراه با تسمه تایم تعویض می‌شود.'],
        ['alternator', 'دینام', 'electric-parts', 'قطعات برقی', 'electric',
            'دینام با خروجی ولتاژ پایدار برای شارژ باتری و تغذیه‌ی سیستم برق.',
            'دینام هنگام کارکرد موتور باتری را شارژ و برق سیستم‌های خودرو را تأمین می‌کند. روشن‌شدن چراغ باتری روی آمپر و ضعیف‌شدن نور چراغ‌ها نشانه‌ی خرابی دینام است. این دینام با آمپر، محل نصب و سوکت یکسان با قطعه‌ی اصلی عرضه می‌شود.'],
        ['starter-motor', 'استارت', 'electric-parts', 'قطعات برقی', 'electric',
            'استارت با گشتاور بالا برای روشن‌شدن سریع موتور.',
            'استارت موتور را هنگام روشن‌کردن خودرو به حرکت درمی‌آورد. صدای تق‌تق یا کند چرخیدن موتور هنگام استارت (با باتری سالم) نشانه‌ی خرابی آن است. این قطعه با محل نصب و اتصالات یکسان با قطعه‌ی اصلی ساخته شده است.'],
        ['ignition-coil', 'کویل', 'electric-parts', 'قطعات برقی', 'electric',
            'کویل جرقه با ولتاژ خروجی بالا و عایق مقاوم در برابر حرارت.',
            'کویل ولتاژ لازم برای جرقه‌ی شمع‌ها را تولید می‌کند. لرزش موتور، افت قدرت و روشن‌شدن چراغ چک از نشانه‌های خرابی کویل است. این قطعه با سوکت و محل نصب یکسان با قطعه‌ی اصلی عرضه می‌شود.'],
        ['clutch-kit', 'دیسک و صفحه کلاچ', 'engine-parts', 'قطعات موتوری', 'engine',
            'کیت کلاچ شامل دیسک، صفحه و بلبرینگ کلاچ.',
            'کلاچ انتقال نیرو از موتور به گیربکس را کنترل می‌کند. بالا آمدن دور موتور بدون افزایش سرعت (سُر خوردن کلاچ) و سنگین‌شدن پدال نشانه‌ی فرسودگی آن است. این کیت شامل دیسک، صفحه و بلبرینگ است تا همه با هم تعویض شوند. (مخصوص خودروهای دنده‌ای)'],
    ];
}

/* پیوندِ محصول: برند-مدل-قطعه؛ اگه اسلاگِ مدل خودش با برند شروع بشه (mvm-x22) تکرار نمی‌شه */
function ap_parts_seed_slug($bslug, $mslug, $pslug) {
    $base = strpos($mslug, $bslug . '-') === 0 ? $mslug : $bslug . '-' . $mslug;
    return sanitize_title($base . '-' . $pslug);
}

/* نامِ انگلیسیِ هر قطعه برای نیمه‌ی انگلیسیِ عنوان («فارسی | English»، مثل سایت اپل) */
function ap_parts_seed_en() {
    return [
        'front-brake-pads' => 'Front Brake Pads', 'rear-brake-pads' => 'Rear Brake Pads',
        'front-bumper' => 'Front Bumper', 'rear-bumper' => 'Rear Bumper',
        'headlight' => 'Headlight', 'taillight' => 'Taillight', 'side-mirror' => 'Side Mirror',
        'front-fender' => 'Front Fender', 'hood' => 'Hood', 'grille' => 'Grille',
        'radiator' => 'Radiator', 'oil-filter' => 'Oil Filter', 'air-filter' => 'Air Filter',
        'spark-plugs' => 'Spark Plugs (Set)',
        'front-brake-discs' => 'Front Brake Discs', 'front-shock-absorbers' => 'Front Shock Absorbers', 'rear-shock-absorbers' => 'Rear Shock Absorbers',
        'front-control-arm' => 'Front Control Arm', 'fog-light' => 'Fog Light', 'ac-condenser' => 'A/C Condenser', 'cabin-filter' => 'Cabin Filter',
        'car-battery' => 'Battery', 'wiper-blades' => 'Wiper Blades', 'fuel-filter' => 'Fuel Filter', 'timing-belt-kit' => 'Timing Belt Kit',
        'water-pump' => 'Water Pump', 'alternator' => 'Alternator', 'starter-motor' => 'Starter Motor', 'ignition-coil' => 'Ignition Coil', 'clutch-kit' => 'Clutch Kit',
    ];
}
/* عنوانِ دوزبانه: «لنت ترمز جلو چری Tiggo 7 | Chery Tiggo 7 Front Brake Pads» */
function ap_parts_seed_title($brand, $model, $pslug, $pname) {
    $brandFa = preg_match('/\((.+)\)/u', $brand, $m) ? $m[1] : $brand;
    $modelEn = trim(preg_replace('/\s*\(.+\)\s*/u', '', $model));
    $brandEn = trim(preg_replace('/\s*\(.+\)\s*/u', '', $brand));
    if ($brandEn === strtoupper($brandEn) && strlen($brandEn) > 3) $brandEn = ucwords(strtolower($brandEn));   /* CHERY → Chery (MVM/JAC/KMC همون می‌مونن) */
    $brandEn = strtr($brandEn, ['Ssangyong' => 'SsangYong', 'Kg Mobility' => 'KG Mobility']);
    $en = ap_parts_seed_en()[$pslug] ?? '';
    $fa = "$pname $brandFa $modelEn";
    $enFull = trim((stripos($modelEn, $brandEn) === 0 ? '' : "$brandEn ") . "$modelEn $en");
    return ['fa' => $fa, 'full' => $en ? "$fa | $enFull" : $fa];
}

function ap_parts_seed_cat($slug, $name) {
    $t = get_term_by('slug', $slug, 'ap_cat');
    if ($t) return (int) $t->term_id;
    $r = wp_insert_term($name, 'ap_cat', ['slug' => $slug]);
    return is_wp_error($r) ? 0 : (int) $r['term_id'];
}

/* خودروهای تمام‌برقی (بدون موتور بنزینی) */
function ap_parts_is_ev($bslug, $mslug, $row = null) {
    $ev = ['byd/dolphin', 'byd/seagull', 'kmc/ej7', 'kmc/j7-ev-plus', 'changan/eado-ev460', 'fownix/fx-ev', 'mvm/mvm-arrizo-5-ev', 'honda/ens1'];
    if (in_array("$bslug/$mslug", $ev, true)) return true;
    return is_array($row[4] ?? null) && (in_array('fashion-ev', $row[4], true) || isset($row[4]['fashion-ev']));
}
/* قطعه‌هایی که فقط در خودروهای دارای موتور احتراقی معنی دارن */
function ap_parts_engine_only() {
    return ['oil-filter', 'air-filter', 'spark-plugs', 'fuel-filter', 'timing-belt-kit', 'water-pump', 'alternator', 'starter-motor', 'ignition-coil', 'clutch-kit'];
}

/* همه‌ی کارهای ساخت (برند × مدل × قطعه) به ترتیب ثابت */
function ap_parts_seed_jobs() {
    $jobs = [];
    /* برندهایی که فعلاً محصولِ خودکار نمی‌گیرن (فقط فهرستِ خودرو)؛ با ۱۳ هزار محصول، بارگذاریِ یکجای فروشگاه از توانِ هاست بیرون می‌زنه */
    $skip = ['mercedes-benz', 'bmw', 'volkswagen', 'toyota', 'lexus', 'nissan', 'mazda', 'mitsubishi', 'suzuki', 'hyundai', 'kia', 'renault', 'peugeot', 'mg', 'geely', 'jetour'];
    foreach (ap_vehicle_seed_data() as [$brand, $bslug, $models]) {
        if (in_array($bslug, $skip, true)) continue;
        foreach ($models as $model_row) {
            [$model, $mslug, $from, $to] = $model_row;
            $ev = ap_parts_is_ev($bslug, $mslug, $model_row ?? null);
            foreach (ap_parts_seed_list() as $part) {
                /* قطعه‌های مخصوصِ موتورِ بنزینی برای خودروهای تمام‌برقی ساخته نمی‌شن */
                if ($ev && in_array($part[0], ap_parts_engine_only(), true)) continue;
                $jobs[] = [$brand, $bslug, $model, $mslug, $from, $to, $part];
            }
        }
    }
    return $jobs;
}

function ap_parts_seed_model_term($bslug, $mslug) {
    $b = get_term_by('slug', $bslug, 'ap_vehicle');
    if (!$b) return 0;
    $kids = get_terms(['taxonomy' => 'ap_vehicle', 'parent' => $b->term_id, 'hide_empty' => false]);
    if (is_wp_error($kids)) return 0;
    foreach ($kids as $k) {
        if ($k->slug === $mslug || $k->slug === $bslug . '-' . $mslug) return (int) $k->term_id;
        if (strpos($k->slug, $bslug . '-' . $mslug . '-') === 0 || strpos($k->slug, $mslug . '-') === 0 && preg_match('/-\d+$/', $k->slug)) return (int) $k->term_id;   /* اسلاگِ پیشوند/پسوندخورده (کدهای مشترک بین برندها) */
    }
    return 0;
}

/* محصولِ این کار از قبل هست؟ با پیوند، یا با عنوانِ فارسی (با/بدونِ نیمه‌ی انگلیسی)، یا با کلیدِ ساخت.
   قبلاً فقط با پیوند چک می‌شد و وقتی پیوندها عوض شده بودن، همه‌ی محصولات دوباره ساخته شدن. */
function ap_parts_seed_exists($slug, $fa) {
    global $wpdb;
    $statuses = "('publish','draft','pending','private','future')";
    if ($wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_status IN $statuses AND post_name = %s LIMIT 1", $slug))) return true;
    if ($wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ap_seed_key' AND meta_value = %s LIMIT 1", $slug))) return true;
    return (bool) $wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_status IN $statuses AND (post_title = %s OR post_title LIKE %s) LIMIT 1",
        $fa, $wpdb->esc_like($fa . ' |') . '%'));
}

function ap_parts_seed_create($job) {
    [$brand, $bslug, $model, $mslug, $from, $to, $part] = $job;
    [$pslug, $pname, $cslug, $cname, $icon, $short, $para] = $part;

    $slug = ap_parts_seed_slug($bslug, $mslug, $pslug);
    if (ap_parts_seed_exists($slug, ap_parts_seed_title($brand, $model, $pslug, $pname)['fa'])) return;

    /* «CHERY (چری)» ← «چری» و «MVM X22 (ام‌وی‌ام ایکس ۲۲)» ← «MVM X22» */
    $brandFa = preg_match('/\((.+)\)/u', $brand, $m) ? $m[1] : $brand;
    $modelEn = trim(preg_replace('/\s*\(.+\)\s*/u', '', $model));
    $brandEn = trim(preg_replace('/\s*\(.+\)\s*/u', '', $brand));
    $yearsFa = strtr("$from تا $to", ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);

    $title = ap_parts_seed_title($brand, $model, $pslug, $pname)['full'];
    $content = '<p>' . esc_html($para) . '</p>'
        . '<h3>سازگاری</h3><ul>'
        . '<li>برند: ' . esc_html("$brandEn ($brandFa)") . '</li>'
        . '<li>مدل: ' . esc_html($model) . '</li>'
        . '<li>سال ساخت: ' . esc_html($yearsFa) . '</li></ul>'
        . '<h3>نکات خرید</h3><ul>'
        . '<li>پیش از سفارش، مدل و سال ساخت خودرو را با کارت خودرو تطبیق دهید.</li>'
        . '<li>برای اطمینان از سازگاری، کد فنی (OEM) قطعه‌ی فعلی یا شماره‌ی شاسی را برای ما بفرستید.</li>'
        . '<li>قیمت و موجودی بر اساس نوع قطعه (اورجینال یا آفترمارکت) متفاوت است؛ برای استعلام تماس بگیرید.</li></ul>';

    $pid = wp_insert_post([
        'post_type'    => 'ap_product',
        'post_status'  => 'publish',
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_content' => $content,
    ]);
    if (is_wp_error($pid) || !$pid) return;

    update_post_meta($pid, '_ap_seed_key', $slug);
    update_post_meta($pid, '_ap_price', 0);
    update_post_meta($pid, '_ap_short_desc', $short);
    update_post_meta($pid, '_ap_spec', "مناسب $brandFa $modelEn ($yearsFa)");
    update_post_meta($pid, '_ap_icon', $icon);
    update_post_meta($pid, '_ap_color', '#ff3b4e');
    update_post_meta($pid, 'rank_math_focus_keyword', "$pname $modelEn");
    update_post_meta($pid, 'rank_math_title', "خرید $pname $brandFa $modelEn | قیمت و مشخصات %sep% %sitename%");
    update_post_meta($pid, 'rank_math_description', mb_substr("خرید $pname $brandFa $modelEn مدل $yearsFa با ضمانت اصالت و ارسال سریع. $short", 0, 158));

    $cat = ap_parts_seed_cat($cslug, $cname);
    if ($cat) wp_set_post_terms($pid, [$cat], 'ap_cat');
    $mid = ap_parts_seed_model_term($bslug, $mslug);
    if ($mid) wp_set_post_terms($pid, [$mid], 'ap_vehicle');
}

/* یک دسته از محصولات رو می‌سازه؛ true = هنوز کار مونده. با قفل، تا دو اجرا همزمان نشن. */
function ap_parts_seed_run($batch) {
    /* اول باید برند/مدل‌ها ساخته شده باشن و تکراری‌ها پاک شده باشن */
    if (get_option('ap_vehicle_seed') !== AP_VEHICLE_SEED_VERSION) return false;
    if (get_option('ap_dupe_cleanup_v1') !== 'done') return true;
    if (!get_option('ap_mvm_move_v1')) return true;   /* اول محصولاتِ MVM به برندِ خودشون منتقل بشن، وگرنه تکراری ساخته می‌شن */
    $state = get_option('ap_parts_seed', ['v' => '', 'done' => 0]);
    if (($state['v'] ?? '') !== AP_PARTS_SEED_VERSION) $state = ['v' => AP_PARTS_SEED_VERSION, 'done' => 0];
    $jobs = ap_parts_seed_jobs();
    if ($state['done'] >= count($jobs)) return false;
    if (get_transient('ap_parts_seed_lock')) return true;
    set_transient('ap_parts_seed_lock', 1, 45);
    @set_time_limit(300);
    wp_defer_term_counting(true);
    $end = min(count($jobs), $state['done'] + $batch);
    for ($i = $state['done']; $i < $end; $i++) ap_parts_seed_create($jobs[$i]);
    wp_defer_term_counting(false);
    $state['done'] = $end;
    update_option('ap_parts_seed', $state, false);
    delete_transient('ap_vehicles_cache_v2');
    delete_transient('ap_products_cache_v3');
    delete_transient('ap_parts_seed_lock');
    return $end < count($jobs);
}

/* خودکار در پس‌زمینه: هر دقیقه یک دسته با «کرون» وردپرس (بدونِ نیاز به رفرش یا کلیک).
   کرونِ وردپرس با هر بازدید از سایت، جدا از صفحه و بدونِ کند کردنش اجرا می‌شه. */
add_filter('cron_schedules', function ($s) {
    $s['ap_every_minute'] = ['interval' => 60, 'display' => 'هر دقیقه'];
    return $s;
});
add_action('init', function () {
    $pending = get_option('ap_parts_seed_pending', 1);
    if ($pending && !wp_next_scheduled('ap_parts_seed_tick')) wp_schedule_event(time() + 30, 'ap_every_minute', 'ap_parts_seed_tick');
});
add_action('ap_parts_seed_tick', function () {
    $more = ap_parts_seed_run(AP_PARTS_SEED_BATCH);
    if (!$more && get_option('ap_dupe_cleanup_v1') === 'done') {
        update_option('ap_parts_seed_pending', 0, false);
        wp_clear_scheduled_hook('ap_parts_seed_tick');
    } else {
        update_option('ap_parts_seed_pending', 1, false);
        if (function_exists('spawn_cron')) spawn_cron();   /* دسته‌ی بعدی بی‌معطلی */
    }
});
/* وقتی نسخه‌ی فهرست بالا می‌ره (قطعه/مدل جدید)، دوباره فعال بشه */
add_action('init', function () {
    if (get_option('ap_parts_seed_pending_v') !== AP_PARTS_SEED_VERSION) {
        update_option('ap_parts_seed_pending', 1, false);
        update_option('ap_parts_seed_pending_v', AP_PARTS_SEED_VERSION, false);
    }
}, 5);
/* وقتی مدیر توی پیشخوانه، همون موقع هم یک دسته ساخته می‌شه (سریع‌تر تموم بشه) */
add_action('admin_init', function () {
    if (!current_user_can('manage_options') || wp_doing_ajax()) return;
    ap_parts_seed_run(AP_PARTS_SEED_BATCH);
}, 20);

add_action('wp_ajax_ap_seed_progress', function () {
    if (!current_user_can('manage_options')) wp_send_json(null);
    @ignore_user_abort(true);
    /* پاک‌سازیِ تکراری‌ها هنوز تموم نشده؟ همون جلو بره */
    if (get_option('ap_dupe_cleanup_v1') !== 'done') { do_action('ap_dupe_cleanup_step'); }
    else ap_parts_seed_run(10);   /* دسته‌های کوچیک تا هیچ‌وقت به محدودیتِ زمانِ هاست نخوره */
    $state = get_option('ap_parts_seed', []);
    wp_send_json(['done' => (int) ($state['done'] ?? 0), 'total' => count(ap_parts_seed_jobs()), 'cleanup' => get_option('ap_dupe_cleanup_v1') === 'done']);
});

add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    $state = get_option('ap_parts_seed', []);
    $total = count(ap_parts_seed_jobs());
    $done = (int) ($state['done'] ?? 0);
    if (($state['v'] ?? '') !== AP_PARTS_SEED_VERSION || $done >= $total) return;
    printf('<div class="notice notice-info" id="apSeedNotice"><p>در حال ساخت خودکارِ محصولات: <b id="apSeedDone">%d</b> از %d — کاری لازم نیست انجام بدید؛ در پس‌زمینه ادامه پیدا می‌کنه.</p></div>', $done, $total);
    /* زنجیره‌ای: هر جواب که رسید، فوراً دسته‌ی بعدی درخواست می‌شه؛ خطا = ۵ ثانیه بعد دوباره، و متنِ خطا نمایش داده می‌شه */
    echo '<script>(function(){var url=' . wp_json_encode(admin_url('admin-ajax.php?action=ap_seed_progress')) . ';var fails=0;'
        . 'function msg(t){var n=document.getElementById("apSeedErr");if(!n){n=document.createElement("p");n.id="apSeedErr";n.style.color="#b32d2e";var w=document.getElementById("apSeedNotice");if(w)w.appendChild(n);}n.textContent=t;}'
        . 'function step(){fetch(url,{credentials:"same-origin"}).then(function(r){if(!r.ok)throw new Error("HTTP "+r.status);return r.text();}).then(function(t){var d;try{d=JSON.parse(t);}catch(e){throw new Error("پاسخِ نامعتبر: "+t.slice(0,120));}'
        . 'if(!d)throw new Error("دسترسی نیست");fails=0;msg("");var e=document.getElementById("apSeedDone");if(e)e.textContent=d.done;'
        . 'if(d.done>=d.total){var n=document.getElementById("apSeedNotice");if(n){n.className="notice notice-success";n.innerHTML="<p>ساخت محصولات تموم شد ✅</p>";}return;}setTimeout(step,300);})'
        . '.catch(function(err){fails++;msg("خطا در ادامه‌ی خودکار ("+err.message+")؛ "+(fails<20?"دوباره امتحان می‌شه…":"صفحه رو یک بار رفرش کنید."));if(fails<20)setTimeout(step,5000);});}'
        . 'setTimeout(step,500);})();</script>';
});

/* یک‌بار (نسخه‌ی ۲): همه‌ی محصولاتی که عنوانشون هنوز «|» نداره دوزبانه می‌شن («فارسی | English»).
   نوعِ قطعه از ابتدای عنوانِ فارسی (لنت ترمز جلو، سپر عقب، ...) و خودرو از برند/مدلی که محصول
   بهش وصله پیدا می‌شه؛ پس به شکلِ دقیقِ عنوانِ قبلی وابسته نیست. فقط عنوان عوض می‌شه
   (مستقیم در دیتابیس) و پیوند یکتا و بقیه‌ی اطلاعات دست نمی‌خورن. دسته‌دسته روی هر بازدید
   از سایت اجرا می‌شه تا بدونِ باز کردنِ پیشخوان هم تموم بشه. */
function ap_parts_strip_fa($name) {
    $en = trim(preg_replace('/\s*\(.+\)\s*/u', '', (string) $name));
    if ($en !== '' && $en === strtoupper($en) && strlen($en) > 3) $en = ucwords(strtolower($en));
    return strtr($en, ['Ssangyong' => 'SsangYong', 'Kg Mobility' => 'KG Mobility']);
}
function ap_parts_en_title_for($post_id, $title) {
    $best = null; $bestLen = 0;
    foreach (ap_parts_seed_list() as $part) {
        $len = mb_strlen($part[1]);
        if ($len > $bestLen && mb_strpos($title, $part[1]) === 0) { $best = $part; $bestLen = $len; }
    }
    if (!$best) return '';
    $partEn = ap_parts_seed_en()[$best[0]] ?? '';
    $terms = get_the_terms($post_id, 'ap_vehicle');
    if (!$terms || is_wp_error($terms)) return '';
    /* عمیق‌ترین ترم ← زنجیره تا برند */
    $deep = null; $depth = -1;
    foreach ($terms as $t) { $d = count(get_ancestors($t->term_id, 'ap_vehicle')); if ($d > $depth) { $depth = $d; $deep = $t; } }
    $chain = array_reverse(get_ancestors($deep->term_id, 'ap_vehicle'));
    $chain[] = $deep->term_id;
    $brand = get_term($chain[0], 'ap_vehicle');
    $model = isset($chain[1]) ? get_term($chain[1], 'ap_vehicle') : null;
    if (!$brand || is_wp_error($brand) || !$model || is_wp_error($model) || $partEn === '') return '';
    $brandEn = ap_parts_strip_fa($brand->name);
    $modelEn = trim(preg_replace('/\s*\(.+\)\s*/u', '', (string) $model->name));   /* مدل همون‌طور که هست (MVM X22، نه Mvm X22) */
    if ($brandEn === '' || $modelEn === '' || preg_match('/[^\x00-\x7f]/', $brandEn . $modelEn)) return '';
    return trim((stripos($modelEn, $brandEn) === 0 ? '' : "$brandEn ") . "$modelEn $partEn");
}
add_action('init', function () {
    if (get_option('ap_parts_title_en_v2') === 'done' || (defined('DOING_AJAX') && DOING_AJAX)) return;
    if (get_transient('ap_parts_title_en_lock')) return;
    set_transient('ap_parts_title_en_lock', 1, 60);
    global $wpdb;
    $last = (int) get_option('ap_parts_title_en_v2_last', 0);
    $rows = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_status IN ('publish','draft','private') AND ID > %d ORDER BY ID ASC LIMIT 150", $last));
    if (!$rows) { update_option('ap_parts_title_en_v2', 'done', false); delete_transient('ap_parts_title_en_lock'); delete_transient('ap_products_cache_v3'); return; }
    $changed = 0;
    foreach ($rows as $r) {
        $last = (int) $r->ID;
        if (strpos($r->post_title, '|') !== false) continue;
        $en = ap_parts_en_title_for((int) $r->ID, trim($r->post_title));
        if ($en === '') continue;
        $wpdb->update($wpdb->posts, ['post_title' => trim($r->post_title) . ' | ' . $en], ['ID' => (int) $r->ID]);
        clean_post_cache((int) $r->ID);
        $changed++;
    }
    update_option('ap_parts_title_en_v2_last', $last, false);
    if ($changed) delete_transient('ap_products_cache_v3');
    delete_transient('ap_parts_title_en_lock');
}, 30);

/* =========================================================================
 * پاک‌سازیِ یک‌باره‌ی محصولاتِ تکراری (آبان ۱۴۰۵).
 * علت: یک کارِ یک‌باره‌ی سایتِ اپل (بازسازیِ پیوندِ همه‌ی محصولات از بخشِ انگلیسیِ عنوان)
 * وقتی اجرا شد که عنوان‌ها هنوز فقط فارسی بودن و پیوندهای انگلیسی رو به نویسه‌گردانیِ
 * فارسی عوض کرد؛ بعد ساختِ دوباره‌ی محصولات، محصولِ قبلی رو با پیوند پیدا نکرد و از
 * همه یک نسخه‌ی دوم ساخت.
 * کار: محصولات بر اساسِ بخشِ فارسیِ عنوان گروه می‌شن؛ در هر گروه یکی نگه داشته می‌شه
 * (قدیمی‌ترین، مگر اینکه فقط نسخه‌ی دیگه قیمت/عکس داشته باشه) و بقیه کاملاً حذف می‌شن.
 * بعد پیوندِ انگلیسیِ تمیز به محصولِ باقی‌مونده برمی‌گرده (پیوندِ فعلی با ۳۰۱ ریدایرکت می‌شه).
 * دسته‌دسته روی هر بازدید اجرا می‌شه تا تموم بشه.
 * ========================================================================= */
function ap_dupe_value($id) {
    $v = 0;
    if ((int) get_post_meta($id, '_ap_price', true) > 0) $v += 4;
    if (get_post_meta($id, '_ap_variant_prices', true)) $v += 2;
    if (get_post_thumbnail_id($id) || get_post_meta($id, '_ap_gallery', true)) $v += 2;
    if ((int) get_comments_number($id) > 0) $v += 1;
    return $v;
}
function ap_dupe_cleanup_step_fn() {
    if (get_option('ap_dupe_cleanup_v1') === 'done') return;
    if (get_transient('ap_dupe_cleanup_lock')) return;
    set_transient('ap_dupe_cleanup_lock', 1, 120);
    @set_time_limit(120);
    global $wpdb;
    $rows = $wpdb->get_results("SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_status IN ('publish','draft','pending','private','future') ORDER BY ID ASC");
    $groups = [];
    foreach ($rows as $r) {
        $key = trim(explode('|', $r->post_title)[0]);
        if ($key === '') continue;
        $groups[$key][] = $r;
    }
    $deleted = 0; $budget = 80; $fixed = 0;
    $log = get_option('ap_dupe_cleanup_log', ['deleted' => 0, 'slugs' => 0]);
    foreach ($groups as $list) {
        if ($budget <= 0) break;
        /* نگه‌داشتنی: بیشترین «ارزش» (قیمت، عکس، ...)، در تساوی قدیمی‌ترین */
        $keep = $list[0]; $best = ap_dupe_value((int) $keep->ID);
        foreach (array_slice($list, 1) as $r) { $v = ap_dupe_value((int) $r->ID); if ($v > $best) { $best = $v; $keep = $r; } }
        $cleanSlug = '';
        foreach ($list as $r) {
            if ((int) $r->ID === (int) $keep->ID) continue;
            /* فقط نسخه‌هایی که خودکار ساخته شدن حذف می‌شن؛ محصولی که خودت ساختی هیچ‌وقت پاک نمی‌شه */
            if (strpos((string) get_post_field('post_content', (int) $r->ID), '<h3>سازگاری</h3>') === false) continue;
            /* پیوندِ نسخه‌ی حذف‌شده فقط وقتی به کار میاد که همون انگلیسیِ تمیزِ عنوانش باشه */
            if (strpos($r->post_title, '|') !== false && ap_slug_from_title($r->post_title) === $r->post_name) $cleanSlug = $r->post_name;
            wp_delete_post((int) $r->ID, true);
            $deleted++; $budget--;
        }
        /* پیوندِ انگلیسیِ تمیز برای محصولِ باقی‌مونده */
        $seedKey = (string) get_post_meta((int) $keep->ID, '_ap_seed_key', true);
        $want = strpos($keep->post_title, '|') !== false ? ap_slug_from_title($keep->post_title) : $cleanSlug;
        if ($want !== '' && $want !== $keep->post_name && count($list) > 1) {
            $new = wp_unique_post_slug($want, (int) $keep->ID, 'publish', 'ap_product', 0);
            if ($new === $want) {
                $wpdb->update($wpdb->posts, ['post_name' => $new], ['ID' => (int) $keep->ID]);
                if ($keep->post_name !== '') add_post_meta((int) $keep->ID, '_wp_old_slug', $keep->post_name);
                clean_post_cache((int) $keep->ID);
                $fixed++;
            }
        }
        if ($seedKey === '' && $want !== '') update_post_meta((int) $keep->ID, '_ap_seed_key', $want);
    }
    $log['deleted'] += $deleted; $log['slugs'] += $fixed;
    update_option('ap_dupe_cleanup_log', $log, false);
    /* وقتی دیگه هیچ گروهی بیش از یک عضو نداشت، تمومه */
    $left = 0; foreach ($groups as $list) if (count($list) > 1) $left++;
    if ($left === 0 || ($deleted === 0 && $fixed === 0)) update_option('ap_dupe_cleanup_v1', 'done', false);
    delete_transient('ap_products_cache_v3');
    delete_transient('ap_dupe_cleanup_lock');
}
add_action('init', function () { if (!(defined('DOING_AJAX') && DOING_AJAX)) ap_dupe_cleanup_step_fn(); }, 20);
add_action('ap_dupe_cleanup_step', 'ap_dupe_cleanup_step_fn');
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    $log = get_option('ap_dupe_cleanup_log');
    if (!$log) return;
    $done = get_option('ap_dupe_cleanup_v1') === 'done';
    if ($done && get_option('ap_dupe_cleanup_notice_shown')) return;
    echo '<div class="notice notice-' . ($done ? 'success' : 'warning') . '"><p><strong>پاک‌سازیِ محصولاتِ تکراری:</strong> '
        . (int) $log['deleted'] . ' نسخه‌ی تکراری حذف شد، پیوندِ ' . (int) $log['slugs'] . ' محصول به انگلیسیِ تمیز برگشت.'
        . ($done ? ' تموم شد ✅' : ' در حال انجام… صفحه رو چند بار رفرش کنید.') . '</p></div>';
    if ($done) update_option('ap_dupe_cleanup_notice_shown', 1, false);
});

/* یک‌بار: «Mvm» → «MVM» در عنوان‌هایی که با نسخه‌ی قبلی دوزبانه شده بودن */
add_action('init', function () {
    if (get_option('ap_title_mvm_fix_v1')) return;
    global $wpdb;
    $wpdb->query("UPDATE {$wpdb->posts} SET post_title = REPLACE(post_title, ' Mvm ', ' MVM ') WHERE post_type = 'ap_product' AND post_title LIKE '%| % Mvm %'");
    update_option('ap_title_mvm_fix_v1', 1, false);
    delete_transient('ap_products_cache_v3');
}, 40);

/* یک‌بار: محصولاتِ MVM که با برندِ «چری» ساخته شده بودن، به برندِ MVM منتقل می‌شن:
   عنوان «... چری MVM X22 | Chery MVM X22 ...» ← «... ام‌وی‌ام X22 | MVM X22 ...»
   و پیوند chery-mvm-x22-... ← mvm-x22-... (پیوندِ قبلی با ۳۰۱ ریدایرکت می‌شه). */
add_action('init', function () {
    if (get_option('ap_mvm_move_v1') || get_option('ap_vehicle_seed') !== AP_VEHICLE_SEED_VERSION) return;
    global $wpdb;
    $rows = $wpdb->get_results("SELECT ID, post_title, post_name FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_name LIKE 'chery-mvm-%'");
    foreach ($rows as $r) {
        $id = (int) $r->ID;
        $title = str_replace(['چری MVM ', '| Chery MVM ', '| Chery Mvm '], ['ام‌وی‌ام ', '| MVM ', '| MVM '], $r->post_title);
        $want = substr($r->post_name, strlen('chery-'));
        $slug = wp_unique_post_slug($want, $id, 'publish', 'ap_product', 0);
        $wpdb->update($wpdb->posts, ['post_title' => $title, 'post_name' => $slug], ['ID' => $id]);
        add_post_meta($id, '_wp_old_slug', $r->post_name);
        update_post_meta($id, '_ap_seed_key', $slug);
        foreach (['_ap_spec', 'rank_math_title', 'rank_math_description', 'rank_math_focus_keyword'] as $k) {
            $v = (string) get_post_meta($id, $k, true);
            if ($v !== '') update_post_meta($id, $k, str_replace('چری MVM ', 'ام‌وی‌ام ', $v));
        }
        clean_post_cache($id);
    }
    update_option('ap_mvm_move_v1', 1, false);
    delete_transient('ap_products_cache_v3');
}, 15);

/* =========================================================================
 * محصولاتِ خودکارِ مدل‌هایی که از فهرست حذف شدن (مطابقِ دیوار): وقتی مدل حذف بشه، محصولِ
 * خودکارش دیگه به هیچ خودرویی وصل نیست. فقط محصولاتی حذف می‌شن که خودکار ساخته شدن و
 * هیچ‌وقت ویرایش نشدن (بدونِ قیمت، بدونِ عکس، بدونِ قیمتِ متغیر). بقیه دست نمی‌خورن.
 * ========================================================================= */
add_action('init', function () {
    $tag = 'v' . AP_VEHICLE_SEED_VERSION;
    if (get_option('ap_orphan_cleanup') === $tag || get_option('ap_vehicle_seed') !== AP_VEHICLE_SEED_VERSION) return;
    if (get_transient('ap_orphan_lock')) return;
    set_transient('ap_orphan_lock', 1, 60);
    global $wpdb;
    $last = (int) get_option('ap_orphan_last_' . $tag, 0);
    $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND ID > %d ORDER BY ID ASC LIMIT 200", $last));
    if (!$ids) { update_option('ap_orphan_cleanup', $tag, false); delete_transient('ap_orphan_lock'); delete_transient('ap_products_cache_v3'); return; }
    foreach ($ids as $id) {
        $id = (int) $id; $last = $id;
        if (strpos((string) get_post_field('post_content', $id), '<h3>سازگاری</h3>') === false) continue;
        $terms = wp_get_object_terms($id, 'ap_vehicle', ['fields' => 'ids']);
        if (!is_wp_error($terms) && $terms) continue;
        if ((int) get_post_meta($id, '_ap_price', true) > 0 || get_post_meta($id, '_ap_variant_prices', true) || get_post_thumbnail_id($id) || get_post_meta($id, '_ap_gallery', true)) continue;
        wp_delete_post($id, true);
    }
    update_option('ap_orphan_last_' . $tag, $last, false);
    delete_transient('ap_orphan_lock');
}, 35);
