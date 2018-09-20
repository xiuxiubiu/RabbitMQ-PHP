# Centos安装RabbitMQ（Yum）

* 官方安装文档

    [Installing on RPM-based Linux (RHEL, CentOS, Fedora, openSUSE)](http://www.rabbitmq.com/install-rpm.html)

* 安装Erlang

    * RabbitMQ依赖的Erlang版本：

    [RabbitMQ Erlang Version Requirements](https://www.rabbitmq.com/which-erlang.html)

    * Erlang安装

        * RabbitMQ团队提供的安装包 (推荐)

        [rabbitmq/erlang-rpm](https://github.com/rabbitmq/erlang-rpm)

        * Erlang官网下载

        [Erlang Solutions](https://www.erlang-solutions.com/resources/download.html)

* 安装RabbitMQ Server

    * 添加公钥

        ```
        rpm --import https://dl.bintray.com/rabbitmq/Keys/rabbitmq-release-signing-key.asc
        ```

    * 添加RabbitMQ Server源

        * centos 7:

            ```
            # In /etc/yum.repos.d/rabbitmq-server.repo
            [bintray-rabbitmq-server]
            name=bintray-rabbitmq-rpm
            baseurl=https://dl.bintray.com/rabbitmq/rpm/rabbitmq-server/v3.7.x/el/7/
            gpgcheck=0
            repo_gpgcheck=0
            enabled=1
            ```

        * centos 6:

            ```
            # In /etc/yum.repos.d/rabbitmq-server.repo
            [bintray-rabbitmq-server]
            name=bintray-rabbitmq-rpm
            baseurl=https://dl.bintray.com/rabbitmq/rpm/rabbitmq-server/v3.7.x/el/6/
            gpgcheck=0
            repo_gpgcheck=0
            enabled=1
            ```

    * 安装RabbitMQ Server

        ```
        yum -y install rabbitmq-server
        ```

* centos开机自启动

    ```
    # 启动
    systemctl start rabbitmq-server

    # 停止
    systemctl stop rabbitmq-server

    # 重启
    systemctl restart rabbitmq-server

    # 运行状态
    systemctl status rabbitmq-server

    # 开机自启动
    systemctl enable rabbitmq-server

    # 关闭开机自启动
    systemctl disable rabbitmq-server

    # 自启动状态
    systemctl list-unit-files | grep rabbitmq-server
    ```
