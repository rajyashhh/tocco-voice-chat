import 'dart:async';
import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter/cupertino.dart';
import 'package:flutter/material.dart';
import 'package:flutter_vap2/flutter_vap.dart';
import 'package:oktoast/oktoast.dart';
import 'package:path_provider/path_provider.dart';

void main() {
  runApp(const MyApp());
}

class MyApp extends StatefulWidget {
  const MyApp({Key? key}) : super(key: key);

  @override
  _MyAppState createState() => _MyAppState();
}

class _MyAppState extends State<MyApp> {
  List<String> downloadPathList;
  bool isDownload = false;
  VapViewController vapViewController;

  @override
  void initState() {
    super.initState();
    initDownloadPath();
  }

  Future<void> initDownloadPath() async {
    Directory appDocDir = await getApplicationDocumentsDirectory();
    String rootPath = appDocDir.path;
    downloadPathList = ["$rootPath/vap_demo1.mp4", "$rootPath/vap_demo2.mp4"];
    print("downloadPathList:$downloadPathList");
  }

  @override
  Widget build(BuildContext context) {
    return OKToast(
      child: MaterialApp(
        home: Scaffold(
          body: Container(
            width: double.infinity,
            height: double.infinity,
            decoration: const BoxDecoration(
              color: Color.fromARGB(255, 140, 41, 43),
              image: DecorationImage(image: AssetImage("static/bg.jpeg")),
            ),
            child: Stack(
              alignment: Alignment.bottomCenter,
              children: [
                Column(
                  mainAxisSize: MainAxisSize.min,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CupertinoButton(
                      color: Colors.purple,
                      onPressed: _download,
                      child: Text(
                          "download video source${isDownload ? "(✅)" : ""}"),
                    ),
                    CupertinoButton(
                      color: Colors.purple,
                      child: const Text("File1 play"),
                      onPressed: () => _playFile(downloadPathList[0]),
                    ),
                    CupertinoButton(
                      color: Colors.purple,
                      child: const Text("File2 play"),
                      onPressed: () => _playFile(downloadPathList[1]),
                    ),
                    CupertinoButton(
                      color: Colors.purple,
                      child: const Text("asset play"),
                      onPressed: () => _playAsset("static/demo.mp4"),
                    ),
                    CupertinoButton(
                      color: Colors.purple,
                      child: const Text("stop play"),
                      onPressed: () => vapViewController.stop(),
                    ),
                    CupertinoButton(
                      color: Colors.purple,
                      onPressed: _queuePlay,
                      child: const Text("queue play"),
                    ),
                    CupertinoButton(
                      color: Colors.purple,
                      onPressed: _cancelQueuePlay,
                      child: const Text("cancel queue play"),
                    ),
                  ],
                ),
                IgnorePointer(
                  // VapView可以通过外层包Container(),设置宽高来限制弹出视频的宽高
                  // VapView can set the width and height through the outer package Container() to limit the width and height of the pop-up video
                  child: SizedBox(
                    width: 414,
                    height: 736,
                    child: VapView(
                      onVapViewCreated: (controller) {
                        vapViewController = controller;
                      },
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  _download() async {
    await Dio().download(
        "http://file.jinxianyun.com/vap_demo1.mp4", downloadPathList[0]);
    await Dio().download(
        "http://file.jinxianyun.com/vap_demo2.mp4", downloadPathList[1]);
    setState(() {
      isDownload = true;
    });
  }

  Future<Map<dynamic, dynamic>> _playFile(String path) async {
    var res = await vapViewController.playPath(path);
    if (res["status"] == "failure") {
      showToast(res["errorMsg"]);
    }
    return res;
  }

  Future<Map<dynamic, dynamic>> _playAsset(String asset) async {
    var res = await vapViewController.playAsset(asset);
    if (res["status"] == "failure") {
      showToast(res["errorMsg"]);
    }
    return res;
  }

  _queuePlay() async {
    // 模拟多个地方同时调用播放,使得队列执行播放。
    // Simultaneously call playback in multiple places, making the queue perform playback.
    QueueUtil.get("vapQueue")
        .addTask(() => vapViewController.playPath(downloadPathList[0]));
    QueueUtil.get("vapQueue")
        .addTask(() => vapViewController.playPath(downloadPathList[1]));
  }

  _cancelQueuePlay() {
    QueueUtil.get("vapQueue").cancelTask();
  }
}
