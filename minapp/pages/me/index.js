Page({
  data: {
    profile: {
      name: '星耀学员',
      grade: '初一',
      city: '南召'
    },
    menu: [
      { title: '测评报告', route: '/pages/me/reports/index' },
      { title: '预报名/预约', route: '/pages/lead/form/index' },
      { title: '学校介绍', route: '/pages/school/index' }
    ]
  },
  goTo(e) {
    const route = e.currentTarget.dataset.route;
    if (route) {
      wx.navigateTo({ url: route });
    }
  }
});
