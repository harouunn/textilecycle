<div class="position-relative" x-data="notificationsBell" x-init="init">
  <button 
    type="button" 
    @click="toggleDropdown" 
    class="notification-bell-btn"
    :class="variant"
    :title="'Notifications' + (unreadCount > 0 ? ' (' + unreadCount + ' non lues)' : '')"
    aria-label="Notifications"
  >
    <i class="bx-bell" :class="iconClass"></i>
    <span 
      x-show="unreadCount > 0" 
      x-text="unreadCount > 9 ? '9+' : unreadCount"
      class="notification-badge"
      :class="badgeClass"
    ></span>
  </button>

  <div 
    x-show="dropdownOpen" 
    @click.outside="dropdownOpen = false"
    x-transition
    class="notification-dropdown"
    :class="dropdownClass"
  >
    <div class="notification-header">
      <h6 class="mb-0">Notifications</h6>
      <button 
        x-show="notifications.length > 0" 
        @click="markAllAsRead" 
        class="btn btn-sm btn-link"
      >
        Tout marquer comme lu
      </button>
    </div>

    <div class="notification-list">
      <template x-if="loading">
        <div class="text-center py-4">
          <div class="spinner-border spinner-border-sm" role="status">
            <span class="visually-hidden">Chargement...</span>
          </div>
        </div>
      </template>

      <template x-if="!loading && notifications.length === 0">
        <div class="text-center py-4 text-muted">
          <i class="bx bx-bell-off fs-1 mb-2"></i>
          <p class="mb-0">Aucune notification</p>
        </div>
      </template>

      <template x-if="!loading && notifications.length > 0">
        <div>
          <template x-for="notification in notifications" :key="notification.id">
            <a 
              :href="notification.lien" 
              @click="markAsRead(notification.id)"
              class="notification-item"
              :class="{ 'notification-unread': !notification.lu, 'notification-priorite': notification.priorite_haute }"
            >
              <div class="d-flex align-items-start">
                <div class="flex-grow-1">
                  <div class="d-flex align-items-center gap-2 mb-1">
                    <strong x-text="notification.titre"></strong>
                    <span 
                      x-show="notification.priorite_haute" 
                      class="badge bg-danger"
                    >
                      Priorité haute
                    </span>
                  </div>
                  <p class="mb-1 text-muted" x-text="notification.message"></p>
                  <small class="text-muted" x-text="notification.created_at"></small>
                </div>
                <span x-show="!notification.lu" class="notification-dot"></span>
              </div>
            </a>
          </template>
        </div>
      </template>
    </div>
  </div>
</div>

<style>
.notification-bell-btn {
  position: relative;
  background: transparent;
  border: none;
  cursor: pointer;
  padding: 0.5rem;
  border-radius: 50%;
  transition: background-color 0.2s;
}

.notification-bell-btn:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.notification-bell-btn.back-office {
  color: inherit;
}

.notification-bell-btn.front-office i {
  font-size: 1.5rem;
}

.notification-badge {
  position: absolute;
  top: 0;
  right: 0;
  min-width: 18px;
  height: 18px;
  padding: 0 4px;
  font-size: 0.75rem;
  font-weight: 600;
  line-height: 18px;
  text-align: center;
  border-radius: 9px;
  background-color: #dc3545;
  color: white;
}

.notification-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 380px;
  max-width: 90vw;
  background: white;
  border: 1px solid rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
  z-index: 1000;
  max-height: 500px;
  display: flex;
  flex-direction: column;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.notification-list {
  overflow-y: auto;
  max-height: 400px;
}

.notification-item {
  display: block;
  padding: 1rem;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  text-decoration: none;
  color: inherit;
  transition: background-color 0.2s;
}

.notification-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.notification-item:last-child {
  border-bottom: none;
}

.notification-unread {
  background-color: rgba(13, 110, 253, 0.05);
}

.notification-priorite {
  border-left: 3px solid #dc3545;
}

.notification-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #0d6efd;
  flex-shrink: 0;
  margin-top: 0.25rem;
}
</style>

<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('notificationsBell', () => ({
    notifications: [],
    unreadCount: 0,
    dropdownOpen: false,
    loading: false,
    variant: '{{ $variant ?? 'back-office' }}',
    iconClass: '{{ $iconClass ?? '' }}',
    badgeClass: '{{ $badgeClass ?? '' }}',
    dropdownClass: '{{ $dropdownClass ?? '' }}',

    init() {
      this.loadNotifications();
      // Rafraîchir le compteur toutes les 30 secondes
      setInterval(() => this.loadUnreadCount(), 30000);
    },

    async loadNotifications() {
      this.loading = true;
      try {
        const response = await fetch('{{ route('notifications.index') }}');
        const data = await response.json();
        this.notifications = data.notifications;
        this.unreadCount = data.non_lues;
      } catch (error) {
        console.error('Erreur lors du chargement des notifications:', error);
      } finally {
        this.loading = false;
      }
    },

    async loadUnreadCount() {
      try {
        const response = await fetch('{{ route('notifications.count') }}');
        const data = await response.json();
        this.unreadCount = data.count;
      } catch (error) {
        console.error('Erreur lors du chargement du compteur:', error);
      }
    },

    toggleDropdown() {
      this.dropdownOpen = !this.dropdownOpen;
      if (this.dropdownOpen && this.notifications.length === 0) {
        this.loadNotifications();
      }
    },

    async markAsRead(notificationId) {
      try {
        const response = await fetch(`/notifications/${notificationId}/read`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });
        const data = await response.json();
        if (data.success) {
          // Mettre à jour localement
          const notification = this.notifications.find(n => n.id === notificationId);
          if (notification) {
            notification.lu = true;
          }
          this.unreadCount = data.non_lues;
        }
      } catch (error) {
        console.error('Erreur lors du marquage comme lu:', error);
      }
    },

    async markAllAsRead() {
      try {
        const response = await fetch('{{ route('notifications.readAll') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
          }
        });
        const data = await response.json();
        if (data.success) {
          // Mettre à jour localement
          this.notifications.forEach(n => n.lu = true);
          this.unreadCount = 0;
        }
      } catch (error) {
        console.error('Erreur lors du marquage tout comme lu:', error);
      }
    }
  }));
});
</script>
