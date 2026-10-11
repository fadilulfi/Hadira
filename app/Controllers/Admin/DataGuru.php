<?php

namespace App\Controllers\Admin;

use App\Models\GuruModel;
use App\Models\UploadModel;
use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Shield\Models\UserModel as ShieldUserModel;

class DataGuru extends BaseController
{
   protected GuruModel $guruModel;
   protected ShieldUserModel $users;
   protected $db;

   protected $guruValidationRules = [
      'nuptk' => [
         'rules' => 'required|max_length[20]|min_length[16]',
         'errors' => [
            'required' => 'NUPTK harus diisi.',
            'is_unique' => 'NUPTK ini telah terdaftar.',
            'min_length' => 'Panjang NUPTK minimal 16 karakter'
         ]
      ],
      'nama' => [
         'rules' => 'required|min_length[3]',
         'errors' => ['required' => 'Nama harus diisi']
      ],
      'jk' => ['rules' => 'required', 'errors' => ['required' => 'Jenis kelamin wajib diisi']],
      'no_hp' => 'required|numeric|max_length[20]|min_length[5]',
      'rfid' => [
         'rules' => 'permit_empty|is_rfid_unique[,guru]',
         'errors' => ['is_rfid_unique' => 'RFID code sudah digunakan.']
      ]
   ];

   protected $accountValidationRules = [
      'username' => [
         'rules' => 'required|max_length[30]|min_length[3]|regex_match[/\\A[a-zA-Z0-9.]+\\z/]',
         'errors' => [
            'required' => 'Username harus diisi.',
            'min_length' => 'Username minimal 3 karakter.',
            'max_length' => 'Username maksimal 30 karakter.',
            'regex_match' => 'Username hanya boleh berisi huruf, angka, dan titik.'
         ]
      ],
      'email' => [
         'rules' => 'required|max_length[254]|valid_email',
         'errors' => [
            'required' => 'Email harus diisi.',
            'valid_email' => 'Format email tidak valid.'
         ]
      ],
   ];

   public function __construct()
   {
      $this->guruModel = new GuruModel();
      $this->db = db_connect();

      $provider = setting('Auth.userProvider');
      $users = model($provider);
      if (! $users instanceof ShieldUserModel) {
         throw new \RuntimeException('Auth.userProvider bukan Shield UserModel yang valid.');
      }
      $this->users = $users;
   }

   public function index()
   {
      return view('admin/data/data-guru', [
         'title' => 'Data Guru',
         'ctx' => 'guru',
      ]);
   }

   public function ambilDataGuru()
   {
      $result = $this->guruModel->getAllGuru();
      return view('admin/data/list-data-guru', [
         'data' => $result,
         'empty' => empty($result)
      ]);
   }

   protected function getGuruAccount(int $idGuru): ?array
   {
      return $this->db->table('users u')
         ->select("u.id, u.username, u.active, ai.secret AS email, GROUP_CONCAT(agu.group ORDER BY agu.group SEPARATOR ',') AS roles", false)
         ->join('auth_identities ai', "ai.user_id = u.id AND ai.type = 'email_password'", 'left')
         ->join('auth_groups_users agu', 'agu.user_id = u.id', 'left')
         ->where('u.id_guru', $idGuru)
         ->where('u.deleted_at', null)
         ->groupBy(['u.id', 'u.username', 'u.active', 'ai.secret'])
         ->get()
         ->getRowArray() ?: null;
   }

   protected function emailExists(string $email, ?int $exceptUserId = null): bool
   {
      $q = $this->db->table('auth_identities')
         ->where('type', 'email_password')
         ->where('secret', $email);
      if ($exceptUserId !== null) {
         $q->where('user_id !=', $exceptUserId);
      }
      return $q->countAllResults() > 0;
   }

   protected function usernameExists(string $username, ?int $exceptUserId = null): bool
   {
      $q = $this->db->table('users')
         ->where('username', $username)
         ->where('deleted_at', null);
      if ($exceptUserId !== null) {
         $q->where('id !=', $exceptUserId);
      }
      return $q->countAllResults() > 0;
   }

   protected function validateAccountInput(string $username, string $email, string $password, string $confirm, ?int $existingUserId = null, bool $passwordRequired = true): array
   {
      $errors = [];

      if ($username === '' || strlen($username) < 3 || strlen($username) > 30 || preg_match('/\\A[a-zA-Z0-9.]+\\z/', $username) !== 1) {
         $errors['username'] = 'Username wajib 3–30 karakter dan hanya boleh huruf, angka, serta titik.';
      } elseif ($this->usernameExists($username, $existingUserId)) {
         $errors['username'] = 'Username ini sudah digunakan.';
      }

      if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
         $errors['email'] = 'Email harus diisi dengan format yang valid.';
      } elseif ($this->emailExists($email, $existingUserId)) {
         $errors['email'] = 'Email ini sudah digunakan oleh akun lain.';
      }

      if ($passwordRequired && strlen($password) < 8) {
         $errors['password'] = 'Password minimal 8 karakter.';
      }

      if ($password !== '' || $passwordRequired) {
         if ($password !== $confirm) {
            $errors['pass_confirm'] = 'Konfirmasi password tidak sama.';
         }
      }

      return $errors;
   }

   protected function createLoginAccount(int $idGuru, string $username, string $email, string $password): int
   {
      $user = $this->users->createNewUser([
         'username' => $username,
         'email' => $email,
         'password' => $password,
      ]);

      if (! $this->users->save($user)) {
         $modelErrors = $this->users->errors();
         throw new \RuntimeException($modelErrors !== [] ? implode(' ', $modelErrors) : 'Gagal membuat akun login guru.');
      }

      $userId = (int) $this->users->getInsertID();
      if ($userId < 1) {
         throw new \RuntimeException('ID akun guru tidak berhasil dibuat.');
      }

      $this->db->table('users')->where('id', $userId)->update([
         'id_guru' => $idGuru,
         'active' => 1,
         'updated_at' => date('Y-m-d H:i:s'),
      ]);

      $createdUser = $this->users->findById($userId);
      if (! $createdUser) {
         throw new \RuntimeException('Akun guru dibuat tetapi gagal dimuat kembali.');
      }

      // Guru selalu mendapatkan group guru. Group tambahan yang sudah dimiliki
      // akun lama tidak disentuh.
      if (! $createdUser->inGroup('guru')) {
         $createdUser->addGroup('guru');
      }

      return $userId;
   }

   protected function updateLoginAccount(int $userId, int $idGuru, string $username, string $email, string $password): void
   {
      $user = $this->users->findById($userId);
      if (! $user) {
         throw new \RuntimeException('Akun guru tidak ditemukan.');
      }

      $this->db->table('users')->where('id', $userId)->update([
         'id_guru' => $idGuru,
         'username' => $username,
         'active' => 1,
         'updated_at' => date('Y-m-d H:i:s'),
      ]);

      $identity = $this->db->table('auth_identities')
         ->where('user_id', $userId)
         ->where('type', 'email_password')
         ->get()
         ->getRowArray();

      if (! $identity) {
         throw new \RuntimeException('Identity email/password akun guru tidak ditemukan.');
      }

      $identityData = [
         'secret' => $email,
         'updated_at' => date('Y-m-d H:i:s'),
      ];

      if ($password !== '') {
         $authConfig = config('Auth');
         $options = [];
         if (defined('PASSWORD_BCRYPT') && $authConfig->hashAlgorithm === PASSWORD_BCRYPT) {
            $options['cost'] = $authConfig->hashCost;
         }
         $hash = password_hash($password, $authConfig->hashAlgorithm, $options);
         if ($hash === false) {
            throw new \RuntimeException('Gagal membuat hash password akun guru.');
         }
         $identityData['secret2'] = $hash;
         $identityData['force_reset'] = 0;
      }

      $this->db->table('auth_identities')->where('id', $identity['id'])->update($identityData);

      $freshUser = $this->users->findById($userId);
      if ($freshUser && ! $freshUser->inGroup('guru')) {
         $freshUser->addGroup('guru');
      }
   }

   public function formTambahGuru()
   {
      return view('admin/data/create/create-data-guru', [
         'ctx' => 'guru',
         'title' => 'Tambah Data Guru',
         'validation' => null,
         'oldInput' => [],
      ]);
   }

   public function saveGuru()
   {
      $profileRules = $this->guruValidationRules;
      if (! $this->validate($profileRules)) {
         return view('/admin/data/create/create-data-guru', [
            'ctx' => 'guru',
            'title' => 'Tambah Data Guru',
            'validation' => $this->validator,
            'oldInput' => $this->request->getVar()
         ]);
      }

      $username = trim((string) $this->request->getPost('username'));
      $email = trim((string) $this->request->getPost('email'));
      $password = (string) $this->request->getPost('password');
      $confirm = (string) $this->request->getPost('pass_confirm');
      $accountErrors = $this->validateAccountInput($username, $email, $password, $confirm, null, true);

      if ($accountErrors !== []) {
         return view('/admin/data/create/create-data-guru', [
            'ctx' => 'guru',
            'title' => 'Tambah Data Guru',
            'validation' => null,
            'accountErrors' => $accountErrors,
            'oldInput' => $this->request->getVar()
         ]);
      }

      $nuptk = (string) $this->request->getPost('nuptk');

      try {
         $this->db->transStart();

         $result = $this->guruModel->createGuru(
            nuptk: $nuptk,
            nama: $this->request->getPost('nama'),
            jenisKelamin: $this->request->getPost('jk'),
            alamat: $this->request->getPost('alamat'),
            noHp: $this->request->getPost('no_hp'),
            rfid: $this->request->getPost('rfid')
         );

         if (! $result) {
            throw new \RuntimeException('Data guru gagal disimpan.');
         }

         $guru = $this->db->table('tb_guru')->where('nuptk', $nuptk)->get()->getRowArray();
         if (! $guru) {
            throw new \RuntimeException('Data guru berhasil disimpan tetapi ID guru tidak ditemukan.');
         }

         $this->createLoginAccount((int) $guru['id_guru'], $username, $email, $password);

         $this->db->transComplete();
         if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Transaksi tambah guru gagal.');
         }

         return redirect()->to(base_url('admin/guru'))->with('msg', 'Data guru dan akun login berhasil dibuat.')->with('error', false);
      } catch (\Throwable $e) {
         $this->db->transRollback();
         log_message('error', 'Gagal tambah guru + akun: {message}', ['message' => $e->getMessage()]);
         return redirect()->back()->withInput()->with('errors', ['general' => 'Gagal membuat data guru dan akun login. Periksa data dan log aplikasi.']);
      }
   }

   public function formEditGuru($id)
   {
      $guru = $this->guruModel->getGuruById($id);
      if (empty($guru)) {
         throw new PageNotFoundException('Data guru dengan id ' . $id . ' tidak ditemukan');
      }

      return view('admin/data/edit/edit-data-guru', [
         'data' => $guru,
         'account' => $this->getGuruAccount((int) $id),
         'ctx' => 'guru',
         'title' => 'Edit Data Guru',
         'validation' => null,
         'accountErrors' => [],
      ]);
   }

   public function updateGuru()
   {
      $idGuru = (int) $this->request->getPost('id');
      $existingAccount = $this->getGuruAccount($idGuru);

      $this->guruValidationRules['rfid']['rules'] = "permit_empty|is_rfid_unique[{$idGuru},guru]";

      if (! $this->validate($this->guruValidationRules)) {
         return view('/admin/data/edit/edit-data-guru', [
            'data' => $this->guruModel->getGuruById($idGuru),
            'account' => $existingAccount,
            'ctx' => 'guru',
            'title' => 'Edit Data Guru',
            'validation' => $this->validator,
            'accountErrors' => [],
            'oldInput' => $this->request->getVar()
         ]);
      }

      $username = trim((string) $this->request->getPost('username'));
      $email = trim((string) $this->request->getPost('email'));
      $password = (string) $this->request->getPost('password');
      $confirm = (string) $this->request->getPost('pass_confirm');

      $accountErrors = $this->validateAccountInput($username, $email, $password, $confirm, $existingAccount['id'] ?? null, $existingAccount === null);
      if ($accountErrors !== []) {
         return view('/admin/data/edit/edit-data-guru', [
            'data' => $this->guruModel->getGuruById($idGuru),
            'account' => $existingAccount,
            'ctx' => 'guru',
            'title' => 'Edit Data Guru',
            'validation' => null,
            'accountErrors' => $accountErrors,
            'oldInput' => $this->request->getVar()
         ]);
      }

      try {
         $this->db->transStart();

         $result = $this->guruModel->updateGuru(
            id: $idGuru,
            nuptk: $this->request->getPost('nuptk'),
            nama: $this->request->getPost('nama'),
            jenisKelamin: $this->request->getPost('jk'),
            alamat: $this->request->getPost('alamat'),
            noHp: $this->request->getPost('no_hp'),
            rfid: $this->request->getPost('rfid')
         );

         if (! $result) {
            throw new \RuntimeException('Data guru gagal diperbarui.');
         }

         if ($existingAccount) {
            $this->updateLoginAccount((int) $existingAccount['id'], $idGuru, $username, $email, $password);
         } else {
            $this->createLoginAccount($idGuru, $username, $email, $password);
         }

         $this->db->transComplete();
         if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Transaksi update guru gagal.');
         }

         return redirect()->to(base_url('admin/guru'))->with('msg', 'Data guru dan akun login berhasil diperbarui.')->with('error', false);
      } catch (\Throwable $e) {
         $this->db->transRollback();
         log_message('error', 'Gagal update guru + akun: {message}', ['message' => $e->getMessage()]);
         return redirect()->back()->withInput()->with('errors', ['general' => 'Gagal memperbarui data guru dan akun login. Periksa data dan log aplikasi.']);
      }
   }

   public function delete($id)
   {
      $idGuru = (int) $id;

      try {
         $this->db->transStart();

         // users.id_guru -> tb_guru tidak memakai ON DELETE CASCADE.
         // Jangan pernah menghapus akun yang memiliki role lain (terutama
         // superadmin/admin/bk/waka). Untuk akun guru murni, akun ikut dihapus;
         // untuk akun multi-role, hubungan ke data guru dilepas agar login tetap ada.
         $account = $this->getGuruAccount($idGuru);
         if ($account) {
            $roles = array_values(array_filter(explode(',', (string) ($account['roles'] ?? ''))));
            $nonGuruRoles = array_values(array_diff($roles, ['guru']));

            if ($nonGuruRoles === []) {
               // Hard delete supaya tidak menyisakan FK users.id_guru.
               $this->db->table('users')->where('id', (int) $account['id'])->delete();
            } else {
               // Akun multi-role tetap dipertahankan. Hanya lepaskan link ke guru.
               $this->db->table('users')->where('id', (int) $account['id'])->update([
                  'id_guru' => null,
                  'updated_at' => date('Y-m-d H:i:s'),
               ]);
            }
         }

         $result = $this->guruModel->delete($idGuru);
         if (! $result) {
            throw new \RuntimeException('Data guru gagal dihapus.');
         }

         $this->db->transComplete();
         if ($this->db->transStatus() === false) {
            throw new \RuntimeException('Transaksi hapus guru gagal.');
         }

         return redirect()->to(base_url('admin/guru'))->with('msg', 'Data guru dan akun login terkait berhasil dihapus.')->with('error', false);
      } catch (\Throwable $e) {
         $this->db->transRollback();
         log_message('error', 'Gagal hapus guru + akun: {message}', ['message' => $e->getMessage()]);
         return redirect()->to(base_url('admin/guru'))->with('msg', 'Gagal menghapus data guru.')->with('error', true);
      }
   }

   public function bulkPost()
   {
      return view('/admin/data/import-guru', [
         'title' => 'Import Guru',
         'ctx' => 'guru',
      ]);
   }

   public function generateCSVObjectPost()
   {
      $uploadModel = new UploadModel();
      $files = glob(FCPATH . 'uploads/tmp/*.txt');
      if (!empty($files)) {
         foreach ($files as $item) {
            @unlink($item);
         }
      }
      $file = $uploadModel->uploadCSVFile('file');
      if (!empty($file) && !empty($file['path'])) {
         $obj = $this->guruModel->generateCSVObject($file['path']);
         if (!empty($obj)) {
            echo json_encode([
               'result' => 1,
               'numberOfItems' => $obj->numberOfItems,
               'txtFileName' => $obj->txtFileName,
            ]);
            exit();
         }
      }
      echo json_encode(['result' => 0]);
   }

   public function importCSVItemPost()
   {
      $txtFileName = inputPost('txtFileName');
      $index = inputPost('index');
      $guru = $this->guruModel->importCSVItem($txtFileName, $index);

      if (!empty($guru) && !isset($guru['status'])) {
         echo json_encode(['result' => 1, 'guru' => $guru, 'index' => $index]);
      } elseif (isset($guru['status']) && $guru['status'] === 'duplicate') {
         echo json_encode(['result' => 2, 'message' => $guru['message'], 'index' => $index]);
      } else {
         echo json_encode(['result' => 0, 'message' => $guru['message'] ?? 'Gagal import data', 'index' => $index]);
      }
   }

   public function downloadCSVFilePost()
   {
      $submit = inputPost('submit');
      $response = \Config\Services::response();
      if ($submit === 'csv_guru_template') {
         return $response->download(FCPATH . 'assets/file/csv_guru_template.csv', null);
      }
      return redirect()->back();
   }
}
