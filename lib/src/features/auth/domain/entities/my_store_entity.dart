import 'package:general/src/core/index.dart';

class MyStoreEntity extends Equatable {
  final dynamic coupons;
  final dynamic coins;
  final dynamic coinsNew;
  final dynamic diamonds;
  final dynamic agentUsd;
  final dynamic userUsd;
  final dynamic walletBalance;
  final dynamic withdrawaledUsd;
  final dynamic silverCoin;
  final dynamic id;
  final dynamic pendingDollar;
  final dynamic roomSalary;

  // Constructor
  const MyStoreEntity({
     this.coupons,
     this.coins,
     this.coinsNew,
     this.diamonds,
     this.agentUsd,
     this.userUsd,
     this.walletBalance,
     this.silverCoin,
     this.id,
     this.pendingDollar,
     this.roomSalary,
     this.withdrawaledUsd,
  });

  // CopyWith method
  MyStoreEntity copyWith({
    dynamic coupons,
    dynamic coins,
    dynamic coinsNew,
    dynamic diamonds,
    dynamic agentUsd,
    dynamic userUsd,
    dynamic walletBalance,
    dynamic silverCoin,
    dynamic id,
    dynamic pendingDollar,
    dynamic roomSalary,
    dynamic withdrawaledUsd,
  }) {
    return MyStoreEntity(
      coupons: coupons ?? this.coupons,
      coins: coins ?? this.coins,
      coinsNew: coinsNew ?? this.coinsNew,
      diamonds: diamonds ?? this.diamonds,
      agentUsd: agentUsd ?? this.agentUsd,
      userUsd: userUsd ?? this.userUsd,
      walletBalance: walletBalance ?? this.walletBalance,
      silverCoin: silverCoin ?? this.silverCoin,
      id: id ?? this.id,
      pendingDollar: pendingDollar ?? this.pendingDollar,
      roomSalary: roomSalary ?? this.roomSalary,
      withdrawaledUsd: withdrawaledUsd ?? this.withdrawaledUsd,
    );
  }

  @override
  List<Object?> get props => [
    coupons,
    coins,
    coinsNew,
    diamonds,
    agentUsd,
    userUsd,
    walletBalance,
    silverCoin,
    id,
    pendingDollar,
    roomSalary,
    withdrawaledUsd,
  ];
}
