<!-- ===== SIDEBAR MENU ===== -->
<div class="sidebar" id="sidebar">
    <h2>Menu</h2>
    <ul>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
            <a href="index.php"><i class="fa fa-home"></i> Accueil</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'parametrage.php' ? 'active' : '' ?>">
            <a href="parametrage.php"><i class="fa fa-cog"></i> Paramétrage</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'historique.php' ? 'active' : '' ?>">
            <a href="historique.php"><i class="fa fa-history"></i> Historique</a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'ia.php' ? 'active' : '' ?>">
            <a href="ia.php"><i class="fa fa-android"></i> Assistant IA</a>
        </li>
    </ul>
</div>
