/**
 * IncluAccess — Built-in panel translations
 * Shared by the frontend widget and the admin Languages screen.
 *
 * Each language: n = native name, d = text direction, s = labels in KEYS order.
 * Site owners can override any label, and add their own languages, in the admin.
 */
window.IncluAccessI18n = {
  KEYS: [
    'panel_title', 'open_menu', 'close',
    'group_visual', 'group_reading', 'group_motion',
    'font_size', 'high_contrast', 'invert', 'grayscale',
    'highlight_links', 'readable_font', 'letter_spacing',
    'pause_anim', 'large_cursor',
    'decrease', 'increase', 'reset'
  ],
  langs: {
    en: { n: 'English', d: 'ltr', s: ['Accessibility', 'Open accessibility menu', 'Close', 'Display', 'Reading', 'Motion & pointer', 'Text Size', 'High Contrast', 'Invert Colors', 'Grayscale', 'Highlight Links', 'Readable Font', 'Letter Spacing', 'Pause Animations', 'Large Cursor', 'Decrease', 'Increase', 'Reset All'] },
    es: { n: 'Español', d: 'ltr', s: ['Accesibilidad', 'Abrir menú de accesibilidad', 'Cerrar', 'Visualización', 'Lectura', 'Movimiento y puntero', 'Tamaño del texto', 'Alto contraste', 'Invertir colores', 'Escala de grises', 'Resaltar enlaces', 'Fuente legible', 'Espaciado de letras', 'Pausar animaciones', 'Cursor grande', 'Disminuir', 'Aumentar', 'Restablecer todo'] },
    fr: { n: 'Français', d: 'ltr', s: ['Accessibilité', 'Ouvrir le menu d’accessibilité', 'Fermer', 'Affichage', 'Lecture', 'Mouvement et pointeur', 'Taille du texte', 'Contraste élevé', 'Inverser les couleurs', 'Niveaux de gris', 'Surligner les liens', 'Police lisible', 'Espacement des lettres', 'Mettre en pause les animations', 'Grand curseur', 'Diminuer', 'Augmenter', 'Tout réinitialiser'] },
    de: { n: 'Deutsch', d: 'ltr', s: ['Barrierefreiheit', 'Menü für Barrierefreiheit öffnen', 'Schließen', 'Anzeige', 'Lesen', 'Bewegung & Zeiger', 'Textgröße', 'Hoher Kontrast', 'Farben umkehren', 'Graustufen', 'Links hervorheben', 'Lesbare Schrift', 'Zeichenabstand', 'Animationen anhalten', 'Großer Mauszeiger', 'Verkleinern', 'Vergrößern', 'Alles zurücksetzen'] },
    it: { n: 'Italiano', d: 'ltr', s: ['Accessibilità', 'Apri il menu di accessibilità', 'Chiudi', 'Visualizzazione', 'Lettura', 'Movimento e puntatore', 'Dimensione del testo', 'Contrasto elevato', 'Inverti colori', 'Scala di grigi', 'Evidenzia link', 'Carattere leggibile', 'Spaziatura lettere', 'Metti in pausa le animazioni', 'Cursore grande', 'Diminuisci', 'Aumenta', 'Ripristina tutto'] },
    pt: { n: 'Português', d: 'ltr', s: ['Acessibilidade', 'Abrir menu de acessibilidade', 'Fechar', 'Exibição', 'Leitura', 'Movimento e cursor', 'Tamanho do texto', 'Alto contraste', 'Inverter cores', 'Escala de cinza', 'Destacar links', 'Fonte legível', 'Espaçamento entre letras', 'Pausar animações', 'Cursor grande', 'Diminuir', 'Aumentar', 'Redefinir tudo'] },
    nl: { n: 'Nederlands', d: 'ltr', s: ['Toegankelijkheid', 'Toegankelijkheidsmenu openen', 'Sluiten', 'Weergave', 'Lezen', 'Beweging & aanwijzer', 'Tekstgrootte', 'Hoog contrast', 'Kleuren omkeren', 'Grijstinten', 'Links markeren', 'Leesbaar lettertype', 'Letterafstand', 'Animaties pauzeren', 'Grote cursor', 'Verkleinen', 'Vergroten', 'Alles resetten'] },
    pl: { n: 'Polski', d: 'ltr', s: ['Dostępność', 'Otwórz menu dostępności', 'Zamknij', 'Wyświetlanie', 'Czytanie', 'Ruch i kursor', 'Rozmiar tekstu', 'Wysoki kontrast', 'Odwróć kolory', 'Skala szarości', 'Wyróżnij linki', 'Czytelna czcionka', 'Odstępy między literami', 'Wstrzymaj animacje', 'Duży kursor', 'Zmniejsz', 'Zwiększ', 'Resetuj wszystko'] },
    sv: { n: 'Svenska', d: 'ltr', s: ['Tillgänglighet', 'Öppna tillgänglighetsmenyn', 'Stäng', 'Visning', 'Läsning', 'Rörelse och pekare', 'Textstorlek', 'Hög kontrast', 'Invertera färger', 'Gråskala', 'Markera länkar', 'Lättläst typsnitt', 'Teckenavstånd', 'Pausa animationer', 'Stor muspekare', 'Minska', 'Öka', 'Återställ allt'] },
    ro: { n: 'Română', d: 'ltr', s: ['Accesibilitate', 'Deschide meniul de accesibilitate', 'Închide', 'Afișare', 'Citire', 'Mișcare și cursor', 'Dimensiunea textului', 'Contrast ridicat', 'Inversează culorile', 'Tonuri de gri', 'Evidențiază linkurile', 'Font lizibil', 'Spațiere între litere', 'Oprește animațiile', 'Cursor mare', 'Micșorează', 'Mărește', 'Resetează tot'] },
    cs: { n: 'Čeština', d: 'ltr', s: ['Přístupnost', 'Otevřít nabídku přístupnosti', 'Zavřít', 'Zobrazení', 'Čtení', 'Pohyb a ukazatel', 'Velikost textu', 'Vysoký kontrast', 'Invertovat barvy', 'Odstíny šedi', 'Zvýraznit odkazy', 'Čitelné písmo', 'Rozestup písmen', 'Pozastavit animace', 'Velký kurzor', 'Zmenšit', 'Zvětšit', 'Obnovit vše'] },
    tr: { n: 'Türkçe', d: 'ltr', s: ['Erişilebilirlik', 'Erişilebilirlik menüsünü aç', 'Kapat', 'Görünüm', 'Okuma', 'Hareket ve imleç', 'Metin boyutu', 'Yüksek kontrast', 'Renkleri ters çevir', 'Gri tonlama', 'Bağlantıları vurgula', 'Okunaklı yazı tipi', 'Harf aralığı', 'Animasyonları duraklat', 'Büyük imleç', 'Azalt', 'Artır', 'Tümünü sıfırla'] },
    el: { n: 'Ελληνικά', d: 'ltr', s: ['Προσβασιμότητα', 'Άνοιγμα μενού προσβασιμότητας', 'Κλείσιμο', 'Εμφάνιση', 'Ανάγνωση', 'Κίνηση και δείκτης', 'Μέγεθος κειμένου', 'Υψηλή αντίθεση', 'Αντιστροφή χρωμάτων', 'Κλίμακα του γκρι', 'Επισήμανση συνδέσμων', 'Ευανάγνωστη γραμματοσειρά', 'Απόσταση γραμμάτων', 'Παύση κινήσεων', 'Μεγάλος δείκτης', 'Μείωση', 'Αύξηση', 'Επαναφορά όλων'] },
    ru: { n: 'Русский', d: 'ltr', s: ['Доступность', 'Открыть меню доступности', 'Закрыть', 'Отображение', 'Чтение', 'Движение и курсор', 'Размер текста', 'Высокий контраст', 'Инвертировать цвета', 'Оттенки серого', 'Выделить ссылки', 'Удобочитаемый шрифт', 'Межбуквенный интервал', 'Остановить анимацию', 'Большой курсор', 'Уменьшить', 'Увеличить', 'Сбросить всё'] },
    uk: { n: 'Українська', d: 'ltr', s: ['Доступність', 'Відкрити меню доступності', 'Закрити', 'Відображення', 'Читання', 'Рух і курсор', 'Розмір тексту', 'Висока контрастність', 'Інвертувати кольори', 'Відтінки сірого', 'Виділити посилання', 'Зручний для читання шрифт', 'Міжлітерний інтервал', 'Зупинити анімацію', 'Великий курсор', 'Зменшити', 'Збільшити', 'Скинути все'] },
    ar: { n: 'العربية', d: 'rtl', s: ['إمكانية الوصول', 'فتح قائمة إمكانية الوصول', 'إغلاق', 'العرض', 'القراءة', 'الحركة والمؤشر', 'حجم الخط', 'تباين عالٍ', 'عكس الألوان', 'تدرج الرمادي', 'إبراز الروابط', 'خط مقروء', 'تباعد الأحرف', 'إيقاف الحركة', 'مؤشر كبير', 'تقليل', 'زيادة', 'إعادة تعيين'] },
    he: { n: 'עברית', d: 'rtl', s: ['נגישות', 'פתיחת תפריט נגישות', 'סגירה', 'תצוגה', 'קריאה', 'תנועה וסמן', 'גודל גופן', 'ניגודיות גבוהה', 'היפוך צבעים', 'גווני אפור', 'הדגשת קישורים', 'גופן קריא', 'ריווח אותיות', 'השהיית אנימציות', 'סמן גדול', 'הקטנה', 'הגדלה', 'איפוס הכל'] },
    fa: { n: 'فارسی', d: 'rtl', s: ['دسترس‌پذیری', 'باز کردن منوی دسترس‌پذیری', 'بستن', 'نمایش', 'خواندن', 'حرکت و نشانگر', 'اندازه متن', 'کنتراست بالا', 'معکوس کردن رنگ‌ها', 'حالت خاکستری', 'برجسته کردن پیوندها', 'فونت خوانا', 'فاصله حروف', 'توقف پویانمایی‌ها', 'نشانگر بزرگ', 'کاهش', 'افزایش', 'بازنشانی همه'] },
    ur: { n: 'اردو', d: 'rtl', s: ['رسائی', 'رسائی کا مینو کھولیں', 'بند کریں', 'ڈسپلے', 'مطالعہ', 'حرکت اور پوائنٹر', 'متن کا سائز', 'زیادہ کنٹراسٹ', 'رنگ الٹ دیں', 'گرے اسکیل', 'لنکس نمایاں کریں', 'آسانی سے پڑھا جانے والا فونٹ', 'حروف کا فاصلہ', 'اینیمیشن روکیں', 'بڑا کرسر', 'کم کریں', 'بڑھائیں', 'سب ری سیٹ کریں'] },
    hi: { n: 'हिन्दी', d: 'ltr', s: ['सुलभता', 'सुलभता मेनू खोलें', 'बंद करें', 'प्रदर्शन', 'पढ़ना', 'गति और पॉइंटर', 'टेक्स्ट का आकार', 'उच्च कंट्रास्ट', 'रंग उलटें', 'ग्रेस्केल', 'लिंक हाइलाइट करें', 'पठनीय फ़ॉन्ट', 'अक्षरों के बीच दूरी', 'एनिमेशन रोकें', 'बड़ा कर्सर', 'घटाएँ', 'बढ़ाएँ', 'सब रीसेट करें'] },
    bn: { n: 'বাংলা', d: 'ltr', s: ['অ্যাক্সেসিবিলিটি', 'অ্যাক্সেসিবিলিটি মেনু খুলুন', 'বন্ধ করুন', 'প্রদর্শন', 'পড়া', 'গতি ও পয়েন্টার', 'লেখার আকার', 'উচ্চ কনট্রাস্ট', 'রং উল্টান', 'গ্রেস্কেল', 'লিংক হাইলাইট করুন', 'সহজপাঠ্য ফন্ট', 'অক্ষরের ব্যবধান', 'অ্যানিমেশন থামান', 'বড় কার্সর', 'কমান', 'বাড়ান', 'সব রিসেট করুন'] },
    th: { n: 'ไทย', d: 'ltr', s: ['การช่วยการเข้าถึง', 'เปิดเมนูการช่วยการเข้าถึง', 'ปิด', 'การแสดงผล', 'การอ่าน', 'การเคลื่อนไหวและตัวชี้', 'ขนาดตัวอักษร', 'คอนทราสต์สูง', 'กลับสี', 'ระดับสีเทา', 'ไฮไลต์ลิงก์', 'แบบอักษรที่อ่านง่าย', 'ระยะห่างตัวอักษร', 'หยุดภาพเคลื่อนไหว', 'เคอร์เซอร์ขนาดใหญ่', 'ลด', 'เพิ่ม', 'รีเซ็ตทั้งหมด'] },
    vi: { n: 'Tiếng Việt', d: 'ltr', s: ['Trợ năng', 'Mở menu trợ năng', 'Đóng', 'Hiển thị', 'Đọc', 'Chuyển động & con trỏ', 'Cỡ chữ', 'Độ tương phản cao', 'Đảo ngược màu', 'Thang độ xám', 'Làm nổi bật liên kết', 'Phông chữ dễ đọc', 'Giãn cách chữ', 'Tạm dừng hoạt ảnh', 'Con trỏ lớn', 'Giảm', 'Tăng', 'Đặt lại tất cả'] },
    id: { n: 'Bahasa Indonesia', d: 'ltr', s: ['Aksesibilitas', 'Buka menu aksesibilitas', 'Tutup', 'Tampilan', 'Membaca', 'Gerakan & penunjuk', 'Ukuran teks', 'Kontras tinggi', 'Balik warna', 'Skala abu-abu', 'Sorot tautan', 'Font mudah dibaca', 'Jarak huruf', 'Jeda animasi', 'Kursor besar', 'Kurangi', 'Tambah', 'Atur ulang semua'] },
    zh: { n: '简体中文', d: 'ltr', s: ['无障碍', '打开无障碍菜单', '关闭', '显示', '阅读', '动画与指针', '文字大小', '高对比度', '反转颜色', '灰度', '突出显示链接', '易读字体', '字间距', '暂停动画', '大光标', '减小', '增大', '全部重置'] },
    'zh-tw': { n: '繁體中文', d: 'ltr', s: ['無障礙', '開啟無障礙選單', '關閉', '顯示', '閱讀', '動畫與指標', '文字大小', '高對比', '反轉顏色', '灰階', '醒目提示連結', '易讀字型', '字距', '暫停動畫', '大型游標', '減少', '增加', '全部重設'] },
    ja: { n: '日本語', d: 'ltr', s: ['アクセシビリティ', 'アクセシビリティメニューを開く', '閉じる', '表示', '読みやすさ', '動きとポインター', '文字サイズ', 'ハイコントラスト', '色を反転', 'グレースケール', 'リンクを強調', '読みやすいフォント', '文字間隔', 'アニメーションを停止', '大きいカーソル', '小さく', '大きく', 'すべてリセット'] },
    ko: { n: '한국어', d: 'ltr', s: ['접근성', '접근성 메뉴 열기', '닫기', '화면', '읽기', '움직임 및 포인터', '글자 크기', '고대비', '색상 반전', '흑백', '링크 강조', '읽기 쉬운 글꼴', '자간', '애니메이션 일시 중지', '큰 커서', '줄이기', '늘리기', '모두 초기화'] }
  },

  // Map a page language tag (e.g. "pt-BR", "zh-Hant-HK", "iw") to candidate codes, most specific first.
  candidates: function (tag) {
    tag = String(tag || '').toLowerCase().replace(/_/g, '-').trim();
    if (!tag) return [];
    const parts = tag.split('-');
    const out = [];
    for (let i = parts.length; i > 0; i--) out.push(parts.slice(0, i).join('-'));
    const legacy = { iw: 'he', in: 'id', ji: 'yi' };
    if (legacy[parts[0]]) out.push(legacy[parts[0]]);
    // Traditional Chinese regions/scripts use the zh-tw labels before generic zh.
    if (parts[0] === 'zh' && /(^|-)(tw|hk|mo|hant)(-|$)/.test(tag)) out.splice(out.indexOf('zh'), 0, 'zh-tw');
    return out;
  }
};
