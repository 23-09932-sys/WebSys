<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: loginPage.php');
    exit;
}

$conn = @pg_connect("host=localhost dbname=userdb user=postgres password=April27");
if (!$conn) {
    die('DB connection failed: ' . pg_last_error());
}

$username = $_SESSION['username'];
$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $skills = trim($_POST['skills'] ?? '');
    $education = trim($_POST['education'] ?? '');

    if ($name === '') {
        $error = 'Name is required.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $res = pg_query_params($conn,
            "UPDATE users SET name=$1, contact=$2, email=$3, skills=$4, education=$5 WHERE username=$6",
            [$name, $contact, $email, $skills, $education, $username]
        );
        if ($res) {
            $success = 'Profile updated successfully.';
        } else {
            $error = 'Update failed: ' . pg_last_error($conn);
        }
    }
}

$result = pg_query_params($conn, "SELECT name, contact, email, skills, education FROM users WHERE username=$1", [$username]);
$user = ($result && pg_num_rows($result) === 1) ? pg_fetch_assoc($result) : [];
pg_close($conn);

function esc($v) { return htmlspecialchars($v ?? '', ENT_QUOTES); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Resume - <?php echo esc($user['name'] ?? $username); ?></title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container page-edit">
    <div class="page-header">
      <h1>Edit Resume</h1>
      <hr>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo esc($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo esc($success); ?></div>
    <?php endif; ?>

    <form method="post" class="edit-form" id="editForm" novalidate>
      <div class="form-grid">
        <div class="form-col-left">
          <label class="form-label" for="name">Name <span class="required">*</span></label>
          <input class="form-input" type="text" id="name" name="name" value="<?php echo esc($user['name'] ?? ''); ?>" placeholder="Full name" required autofocus aria-required="true" maxlength="100" />

          <label class="form-label" for="email">Email</label>
          <input class="form-input" type="email" id="email" name="email" value="<?php echo esc($user['email'] ?? ''); ?>" placeholder="you@example.com" aria-describedby="emailHelp" maxlength="150" />
          <div class="field-note" id="emailHelp">We'll only use this to show on your public resume.</div>
          <div class="field-error" id="emailError" hidden></div>

          <label class="form-label" for="contact">Contact</label>
          <input class="form-input" type="tel" id="contact" name="contact" value="<?php echo esc($user['contact'] ?? ''); ?>" placeholder="+63 9xx xxx xxxx" inputmode="tel" pattern="^\+?[0-9\s\-]{7,20}$" maxlength="25" aria-describedby="contactHelp" />
          <div class="field-note" id="contactHelp">Numbers, spaces, plus and dashes allowed.</div>
          <div class="field-error" id="contactError" hidden></div>
        </div>

        <div class="form-col-right">
          <label class="form-label" for="skills">Skills</label>
          <textarea class="form-textarea" id="skills" name="skills" placeholder="Enter each skill on a new line or separate by comma" maxlength="1000" aria-describedby="skillsCount"><?php echo esc($user['skills'] ?? ''); ?></textarea>
          <div class="char-count" id="skillsCount">0 / 1000</div>

          <label class="form-label" for="education">Education</label>
          <textarea class="form-textarea" id="education" name="education" placeholder="Enter each education item on a new line" maxlength="2000" aria-describedby="eduCount"><?php echo esc($user['education'] ?? ''); ?></textarea>
          <div class="char-count" id="eduCount">0 / 2000</div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Save</button>
        <button type="button" class="btn btn-secondary" onclick="window.location.href='resume.php'">Back to Resume</button>
      </div>

      <p class="form-note">Tip: Press Enter to put each skill/education on its own line. Skills can also be comma-separated.</p>
    </form>
  </div>

  <script>
    // Basic client-side enhancements: live validation and char counters
    (function(){
      const form = document.getElementById('editForm');
      const email = document.getElementById('email');
      const contact = document.getElementById('contact');
      const emailError = document.getElementById('emailError');
      const contactError = document.getElementById('contactError');

      const skills = document.getElementById('skills');
      const education = document.getElementById('education');
      const skillsCount = document.getElementById('skillsCount');
      const eduCount = document.getElementById('eduCount');

      function updateCount(el, counter, max){
        counter.textContent = el.value.length + ' / ' + max;
      }

      skills.addEventListener('input', ()=> updateCount(skills, skillsCount, skills.getAttribute('maxlength')));
      education.addEventListener('input', ()=> updateCount(education, eduCount, education.getAttribute('maxlength')));

      updateCount(skills, skillsCount, skills.getAttribute('maxlength'));
      updateCount(education, eduCount, education.getAttribute('maxlength'));

      function setError(el, msgEl, message){
        if (message){
          msgEl.textContent = message;
          msgEl.hidden = false;
          el.classList.add('invalid');
          el.classList.remove('valid');
        } else {
          msgEl.textContent = '';
          msgEl.hidden = true;
          el.classList.remove('invalid');
          el.classList.add('valid');
        }
      }

      email.addEventListener('input', ()=> {
        if (email.value === '') { setError(email, emailError, ''); return; }
        if (email.checkValidity()) setError(email, emailError, '');
        else setError(email, emailError, 'Invalid email address.');
      });

      contact.addEventListener('input', ()=> {
        const pattern = new RegExp(contact.getAttribute('pattern'));
        if (contact.value === '') { setError(contact, contactError, ''); return; }
        if (pattern.test(contact.value)) setError(contact, contactError, '');
        else setError(contact, contactError, 'Invalid phone format.');
      });


      form.addEventListener('submit', function(e){

        email.dispatchEvent(new Event('input'));
        contact.dispatchEvent(new Event('input'));

        if (email.classList.contains('invalid') || contact.classList.contains('invalid')) {
          e.preventDefault();

          const first = form.querySelector('.invalid');
          if (first) first.focus();
        }
      });
    })();
  </script>
</body>
</html>
