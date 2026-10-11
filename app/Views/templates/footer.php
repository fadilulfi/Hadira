<footer class="footer">
   <div class="container-fluid">
      <div class="copyright text-right w-100">
         <span class="font-weight-bold">HADIRA</span>
         <span class="mx-1">•</span>
         <?= esc($generalSettings->school_name ?? 'MAN 1 Padang'); ?>
         <?php if (!empty($generalSettings->copyright)): ?>
            <span class="mx-1">•</span>
            <?= esc($generalSettings->copyright); ?>
         <?php endif; ?>
      </div>
   </div>
</footer>
