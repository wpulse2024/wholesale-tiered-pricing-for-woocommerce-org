<template>
    <el-menu
      :default-active="activeMenu"
      class="custom-sidebar"
      router
      @select="handleSelect"
    >
      <el-menu-item index="/">
        <el-icon><Money /></el-icon>    
        <span>Pricing</span>
      </el-menu-item>
  
      <el-menu-item index="/campaigns">
        <el-icon><Collection /></el-icon>
        <span>Products</span>
      </el-menu-item>

      <el-menu-item index="/settings">
        <el-icon><Setting /></el-icon>
        <span>Settings</span>
      </el-menu-item>

      <el-menu-item index="/comments">
        <el-icon><Document /></el-icon>
        <span>Documentation</span>
      </el-menu-item>
    </el-menu>
  </template>
  
  <script>
  import {
    Setting,
    Money,
    Document,
    Collection

  } from '@element-plus/icons-vue'
  
  export default {
    name: 'SidebarMenu',
    components: {
      Setting,
      Money,
      Document,
      Collection
    },
    data() {
      return {
        activeMenu: this.$route.path, // highlights active route
      };
    },
    watch: {
      '$route.path'(newVal) {
        this.activeMenu = newVal;
      }
    },
    methods: {
      handleSelect(path) {
        this.$router.push(path);
      }
    }
  };
  </script>
  
  <style scoped>
  .custom-sidebar {
    width: 240px;
    border-right: 1px solid #e5e7eb;
    min-height: 100vh;
    padding-top: 16px;
    background-color: white;
    border-radius: 12px;
  }
  
  .el-menu {
    border: none;
  }
  
  .el-menu-item {
    border-radius: 12px;
    margin: 4px 12px;
    padding: 4px 14px;
    color: #374151;
    font-weight: 500;
    height: 40px;
    margin-bottom: 10px;
  }
  
  .el-menu-item.is-active {
    background-color: #f5f6fa;
    color: #111827;
  }
  
  .el-menu-item .el-icon {
    margin-right: 10px;
  }
  </style>