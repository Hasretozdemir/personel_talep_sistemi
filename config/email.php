<?php
declare(strict_types=1);

/**
 * E-posta Gönderme Fonksiyonları
 * PHPMailer kullanmadan basit mail() fonksiyonu ile
 */

/**
 * E-posta gönder
 */
function send_email(string $to, string $subject, string $body, string $from = 'noreply@gazi.gov.tr', string $fromName = 'Gazi IT'): bool {
    $headers = [
        'From: ' . $fromName . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8'
    ];
    
    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Yeni talep bildirimi (Admin'e)
 */
function email_yeni_talep(array $talep, array $adminler): void {
    $subject = 'Yeni Talep: ' . $talep['personel_ad_soyad'];
    
    $body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
            .info-row { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
            .label { font-weight: bold; color: #667eea; }
            .button { display: inline-block; padding: 12px 24px; background: #667eea; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>🔔 Yeni Talep Bildirimi</h2>
            </div>
            <div class="content">
                <p>Merhaba,</p>
                <p>Sisteme yeni bir talep kaydedildi:</p>
                
                <div class="info-row">
                    <span class="label">Talep ID:</span> #' . $talep['id'] . '
                </div>
                <div class="info-row">
                    <span class="label">Personel:</span> ' . htmlspecialchars($talep['personel_ad_soyad']) . '
                </div>
                <div class="info-row">
                    <span class="label">Sicil No:</span> ' . htmlspecialchars($talep['sicil_no']) . '
                </div>
                <div class="info-row">
                    <span class="label">Birim:</span> ' . htmlspecialchars($talep['birim_adi']) . '
                </div>
                <div class="info-row">
                    <span class="label">Sistem:</span> ' . htmlspecialchars($talep['sistem_adi']) . '
                </div>
                <div class="info-row">
                    <span class="label">Talep Notu:</span> ' . htmlspecialchars($talep['talep_notu'] ?? '-') . '
                </div>
                <div class="info-row">
                    <span class="label">Tarih:</span> ' . date('d.m.Y H:i', strtotime($talep['tarih'])) . '
                </div>
                
                <a href="http://127.0.0.1:9000/admin/index.php" class="button">Talebi Görüntüle</a>
                
                <p style="margin-top: 20px; color: #666; font-size: 0.9em;">
                    Bu otomatik bir bildirimdir. Lütfen yanıtlamayın.
                </p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    foreach ($adminler as $admin) {
        if (!empty($admin['email'])) {
            send_email($admin['email'], $subject, $body);
        }
    }
}

/**
 * Talep onaylandı bildirimi (Personele)
 */
function email_talep_onaylandi(array $talep): void {
    if (empty($talep['email'])) {
        return;
    }
    
    $subject = 'Talebiniz Onaylandı - ' . $talep['sistem_adi'];
    
    $body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
            .success-icon { font-size: 48px; text-align: center; margin: 20px 0; }
            .info-row { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
            .label { font-weight: bold; color: #28a745; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>✅ Talebiniz Onaylandı!</h2>
            </div>
            <div class="content">
                <div class="success-icon">🎉</div>
                
                <p>Sayın <strong>' . htmlspecialchars($talep['personel_ad_soyad']) . '</strong>,</p>
                <p>Talebiniz onaylanmıştır. Detaylar aşağıdadır:</p>
                
                <div class="info-row">
                    <span class="label">Talep ID:</span> #' . $talep['id'] . '
                </div>
                <div class="info-row">
                    <span class="label">Sistem:</span> ' . htmlspecialchars($talep['sistem_adi']) . '
                </div>
                <div class="info-row">
                    <span class="label">Onaylayan:</span> ' . htmlspecialchars($talep['admin_ad'] ?? 'Sistem') . '
                </div>
                <div class="info-row">
                    <span class="label">Onay Tarihi:</span> ' . date('d.m.Y H:i') . '
                </div>
                
                <p style="margin-top: 20px;">
                    Erişim bilgileriniz en kısa sürede tarafınıza iletilecektir.
                </p>
                
                <p style="margin-top: 20px; color: #666; font-size: 0.9em;">
                    Sorularınız için Bilgi İşlem birimiyle iletişime geçebilirsiniz.
                </p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    send_email($talep['email'], $subject, $body);
}

/**
 * Talep reddedildi bildirimi (Personele)
 */
function email_talep_reddedildi(array $talep, string $redNeden): void {
    if (empty($talep['email'])) {
        return;
    }
    
    $subject = 'Talebiniz Hakkında - ' . $talep['sistem_adi'];
    
    $body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
            .info-row { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
            .label { font-weight: bold; color: #dc3545; }
            .reason-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>ℹ️ Talep Durumu</h2>
            </div>
            <div class="content">
                <p>Sayın <strong>' . htmlspecialchars($talep['personel_ad_soyad']) . '</strong>,</p>
                <p>Talebiniz değerlendirilmiştir:</p>
                
                <div class="info-row">
                    <span class="label">Talep ID:</span> #' . $talep['id'] . '
                </div>
                <div class="info-row">
                    <span class="label">Sistem:</span> ' . htmlspecialchars($talep['sistem_adi']) . '
                </div>
                
                <div class="reason-box">
                    <strong>Red Nedeni:</strong><br>
                    ' . nl2br(htmlspecialchars($redNeden)) . '
                </div>
                
                <p style="margin-top: 20px;">
                    Detaylı bilgi için Bilgi İşlem birimiyle iletişime geçebilirsiniz.
                </p>
                
                <p style="margin-top: 20px; color: #666; font-size: 0.9em;">
                    Gerekli düzeltmeleri yaptıktan sonra yeni bir talep oluşturabilirsiniz.
                </p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    send_email($talep['email'], $subject, $body);
}

/**
 * Talep notu eklendi bildirimi
 */
function email_talep_notu(array $talep, string $not, string $adminAd): void {
    if (empty($talep['email'])) {
        return;
    }
    
    $subject = 'Talebinize Not Eklendi - #' . $talep['id'];
    
    $body = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 20px; border-radius: 0 0 10px 10px; }
            .note-box { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; border-radius: 5px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>📝 Talebinize Not Eklendi</h2>
            </div>
            <div class="content">
                <p>Sayın <strong>' . htmlspecialchars($talep['personel_ad_soyad']) . '</strong>,</p>
                <p>Talep #' . $talep['id'] . ' için yeni bir not eklendi:</p>
                
                <div class="note-box">
                    ' . nl2br(htmlspecialchars($not)) . '
                </div>
                
                <p><strong>Not Ekleyen:</strong> ' . htmlspecialchars($adminAd) . '</p>
                <p><strong>Tarih:</strong> ' . date('d.m.Y H:i') . '</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    send_email($talep['email'], $subject, $body);
}
