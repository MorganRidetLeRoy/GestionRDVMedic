    </div><!-- /page-body -->
    
  </div><!-- /main-content -->
  
</div><!-- /app-layout -->

<script>
// Ferme sidebar en mobile si clic à l'extérieur
document.addEventListener('click', function(e) {
  const sidebar = document.getElementById('sidebar');
  if (sidebar && window.innerWidth <= 768) {
    if (!sidebar.contains(e.target) && !e.target.closest('#menuBtn')) {
      sidebar.classList.remove('open');
    }
  }
});
</script>
</body>
</html>