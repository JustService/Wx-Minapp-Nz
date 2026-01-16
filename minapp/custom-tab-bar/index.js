Component({
  data: {
    selected: 0,
    color: "#9B8FD1",
    selectedColor: "#C9A9FF",
    list: [
      { pagePath: "pages/home/index", text: "首页", key: "home" },
      { pagePath: "pages/evaluate/index", text: "测评", key: "evaluate" },
      { pagePath: "pages/growth/index", text: "成长", key: "growth" },
      { pagePath: "pages/benefits/index", text: "权益", key: "benefits" },
      { pagePath: "pages/me/index", text: "我的", key: "me" }
    ]
  },
  methods: {
    onTap(e) {
      const path = e.currentTarget.dataset.path;
      const index = e.currentTarget.dataset.index;
      if (!path || index === this.data.selected) return;
      wx.switchTab({ url: "/" + path });
    }
  }
});
