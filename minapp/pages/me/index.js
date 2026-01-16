Page({
  data: {
    profile: {
      name: '同学',
      level: 3,
      points: 128,
      tickets: 2
    },
    menu: [
      {
        title: '预报名信息',
        desc: '仅收集必要信息，可随时撤回或删除',
        value: '未提交',
        route: '/pages/lead/form/index'
      },
      {
        title: '报告历史',
        desc: '查看已完成测评报告',
        value: '4 份',
        route: '/pages/me/reports/index'
      },
      {
        title: '隐私与设置',
        desc: '管理数据授权与删除申请',
        value: '',
        route: ''
      },
      {
        title: '客服与联系方式',
        desc: '如需人工协助可通过此入口联系',
        value: '',
        route: ''
      }
    ]
  },
  onShow() {
    if (typeof this.getTabBar === 'function' && this.getTabBar()) {
      this.getTabBar().setData({ selected: 4 });
    }
  }
  ,
  goTo(e) {
    const route = e.currentTarget.dataset.route;
    if (route) {
      wx.navigateTo({ url: route });
    }
  }
});
