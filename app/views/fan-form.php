<?= csrf_field() ?><?= honeypot_field() ?>
<div class="form-baris">
  <div><label for="fan-name">Nama Lengkap</label><input id="fan-name" name="name" maxlength="120" value="<?= e(old('cerita','name')) ?>" required></div>
  <div><label for="fan-email">Alamat Email</label><input id="fan-email" type="email" name="email" maxlength="150" value="<?= e(old('cerita','email')) ?>" required></div>
</div>
<label for="fan-type">Kategori</label>
<select id="fan-type" name="type" required>
  <option value="">Pilih Kategori</option>
  <?php foreach (['story'=>'Cerita Penggemar','artwork'=>'Karya Penggemar','cover'=>'Cover Lagu'] as $value=>$label): ?>
    <option value="<?= e($value) ?>"<?= old('cerita','type')===$value?' selected':'' ?>><?= e($label) ?></option>
  <?php endforeach; ?>
</select>
<label for="fan-community">Komunitas (opsional)</label>
<select id="fan-community" name="community_id">
  <option value="">Tidak memilih komunitas</option>
  <?php foreach ($communities as $community): ?>
    <option value="<?= (int)$community['id'] ?>"<?= old('cerita','community_id')===(string)$community['id']?' selected':'' ?>><?= e($community['name']) ?> — <?= e($community['city']) ?></option>
  <?php endforeach; ?>
</select>
<label for="fan-content">Pesan / Deskripsi</label>
<textarea id="fan-content" name="content" rows="4" maxlength="2000" required><?= e(old('cerita','content')) ?></textarea>
<label for="fan-file">Berkas karya / cover lagu</label>
<input id="fan-file" type="file" name="karya" accept="image/jpeg,image/png,image/webp,audio/mpeg,.mp3">
<p class="paragraf-kecil">Karya penggemar: JPG, PNG, atau WEBP (maksimal 2 MB). Cover lagu: MP3 (maksimal 8 MB). Berkas wajib untuk karya dan cover lagu, opsional untuk cerita.</p>
<button class="btn btn-merah lebar">Kirim</button>
