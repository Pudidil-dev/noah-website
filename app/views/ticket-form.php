<?= csrf_field() ?><?= honeypot_field() ?>
<div class="form-baris"><input name="full_name" placeholder="Nama Lengkap" value="<?= e(old('tiket', 'full_name')) ?>"
        required><input type="email" name="email" placeholder="Email" value="<?= e(old('tiket', 'email')) ?>" required>
</div>
<div class="form-baris"><input type="tel" name="phone" placeholder="Nomor Telepon"
        value="<?= e(old('tiket', 'phone')) ?>" required><input type="number" min="1" max="10" name="quantity"
        value="<?= e(old('tiket', 'quantity') ?: '1') ?>" required></div><select id="pilih-konser" name="event_id"
    required>
    <option value="">Pilih Konser</option><?php foreach ($events as $event): ?>
        <option value="<?= (int) $event['id'] ?>" <?= old('tiket', 'event_id') === (string) $event['id'] ? ' selected' : '' ?>>
            <?= e($event['city'] . ' — ' . $event['title'] . ' (' . tanggal_id($event['start_at']) . ')') ?></option>
    <?php endforeach; ?>
</select><textarea name="notes" rows="4" placeholder="Catatan tambahan"><?= e(old('tiket', 'notes')) ?></textarea><button
    class="btn btn-merah lebar">Pesan Tiket</button>