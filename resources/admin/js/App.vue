<template>
    <div class="wtpfw_admin_settings_wrapper">
        <div class="wtpfw_admin_settings_sidebar">
            <SidebarMenu />
        </div>
        <div class="wtpfw_admin_settings_content">
            <router-view></router-view>
        </div>
    </div>
</template>

<script>
import SidebarMenu from './components/Layout/SidebarMenu.vue';
export default {
    name: 'App',
    components: {
        SidebarMenu
    }
}
</script>

<style scoped>
.wtpfw_admin_settings_wrapper {
    display: flex;
    min-height: 100vh;
    flex-wrap: wrap;
    gap: 20px;
    width: 90%;
    margin: 0 auto;
    max-width: 1200px;
}

.wtpfw_admin_settings_sidebar {
    width: 240px;
}

.wtpfw_admin_settings_content {
    flex: 1;
    background: #fff;
    border-radius: 12px;
}
</style>