import 'package:general/src/core/index.dart';
import 'package:general/src/features/agency/presentation/host_agency/bloc/manager_show_agency/show_agency_bloc.dart';
import 'package:general/src/features/agency/presentation/search_agency_screen/view/show_hosts_agency.dart';
import 'package:general/src/features/agency/presentation/search_agency_screen/view/show_shipping_agency.dart';
import 'package:general/src/features/agency/presentation/charge_agency/view/component/info_charge_agency/bloc/manager_get_charge_agency_info/get_charge_agency_bloc.dart';
import 'package:general/src/features/auth/domain/entities/user_entity.dart';
import 'package:general/src/features/cp/presentation/cp_store/bloc/cp_profile_bloc/cp_profile_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_support/get_user_support_bloc.dart';
import 'package:general/src/features/profile/presentation/profile/view/page/user_profile/bloc/get_user_support/get_user_support_state.dart';
import 'package:general/src/features/theme2_app/user_profile/presentation/widgets/theme2_visitor_cp_card.dart';

/// تاب علاقة - Agency + Family + Supporters + CP
class Theme2VisitorRelationTab extends StatelessWidget {
  final UserEntity? user;
  final GetSupporterBloc getSupporterBloc;
  final CpProfileBloc cpProfileBloc;

  const Theme2VisitorRelationTab({
    super.key,
    required this.user,
    required this.getSupporterBloc,
    required this.cpProfileBloc,
  });

  @override
  Widget build(BuildContext context) {
    return ListView(
      // Nested inside _Theme2GeneralTab's scroll → shrink-wrap and disable its
      // own scrolling so the parent NestedScrollView owns all vertical scroll.
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 12.h),
      children: [
        // Agency section
        if (user?.myAgency != null) ...[
          _SectionTitle(
            title: StringManager.agency1.tr(),
            color: ColorManager.primary,
          ),
          8.hBox,
          _AgencyCard(
            title: user!.myAgency!.name ?? '',
            image: user!.myAgency!.img ?? '',
            memberCount: user!.myAgency!.memberCount?.toString() ?? '0',
            backgroundGradient: const [Color(0xFF7C93FF), Color(0xFF3D5AFE)],
            onTap: () {
              di<ShowAgencyBloc>().add(ShowAgencyEvent(
                  agencyId: user!.myAgency!.id, isFirstLoading: true));
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => const ShowHostsAgency(
                    agencyData: null,
                    isProfile: true,
                  ),
                ),
              );
            },
          ),
          16.hBox,
        ],

        // Shipping Agency section
        if (user?.myShippingAgency != null &&
            ConstantsManager.isHostAgencyVisible) ...[
          _SectionTitle(
            title: StringManager.chargeAgency.tr(),
            color: ColorManager.primary,
          ),
          8.hBox,
          _AgencyCard(
            title: user!.myShippingAgency!.name ?? '',
            image: user!.myShippingAgency!.img ?? '',
            memberCount: '',
            backgroundGradient: const [Color(0xFF60A5FA), Color(0xFF3B82F6)],
            onTap: () {
              di<GetChargeAgencyBloc>().add(GetChargeAgencyEvent(
                  agencyId: user!.myShippingAgency!.id,
                  isFirstLoading: true));
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => ShowShippingAgency(
                    agencyData: null,
                    isProfile: true,
                    id: user!.myShippingAgency!.id,
                  ),
                ),
              );
            },
          ),
          16.hBox,
        ],

        // Family section
        if (user?.familyData != null) ...[
          _SectionTitle(
            title: StringManager.family.tr(),
            color: ColorManager.primary,
          ),
          8.hBox,
          _AgencyCard(
            title: user!.familyData!.name ?? '',
            image: user!.familyData!.img ?? '',
            memberCount: user!.familyData!.memberNum?.toString() ?? '0',
            backgroundGradient: const [Color(0xFF94A3B8), Color(0xFF64748B)],
            onTap: () {
              if (user!.familyData!.ownerFamilyId != 0) {
                Navigator.pushNamed(context, Routes.familyScreen,
                    arguments: '${user!.familyId}');
              } else {
                context.pushNamedRoute(Routes.familyRankPage);
              }
            },
          ),
          16.hBox,
        ],

        // Supporters section
        _SectionTitle(
          title: StringManager.supporters.tr(),
          color: ColorManager.primary,
        ),
        8.hBox,
        BlocBuilder<GetSupporterBloc, GetUserSupporterState>(
          bloc: getSupporterBloc,
          buildWhen: (prev, curr) => prev.topSupport != curr.topSupport,
          builder: (context, state) {
            final supporters = [
              ...(state.topSupport?.topUser ?? []),
              ...(state.topSupport?.otherUsers ?? []),
            ];

            if (supporters.isEmpty) {
              return Padding(
                padding: EdgeInsets.symmetric(vertical: 12.h),
                child: Center(
                  child: Text(
                    StringManager.noDataYet.tr(),
                    style: TextStyle(
                      color: ColorManager.secondaryText,
                      fontSize: 13.sp,
                    ),
                  ),
                ),
              );
            }

            return GestureDetector(
              onTap: () {
                Navigator.pushNamed(context, Routes.supportScreen,
                    arguments: SupportScreenParam(
                      userId: user?.id.toString() ?? '',
                      getSupporterBloc: getSupporterBloc,
                    ));
              },
              child: SizedBox(
                height: 80.h,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: supporters.length > 8 ? 8 : supporters.length,
                  separatorBuilder: (_, __) => 12.wBox,
                  itemBuilder: (context, index) {
                    final supporter = supporters[index];
                    return GestureDetector(
                      onTap: () {
                        Methods().userProfileNavigator(
                          context: context,
                          userId: '${supporter.id}',
                        );
                      },
                      child: Column(
                        children: [
                          UserImage(
                            image: supporter.image ?? '',
                            displayName: supporter.name ?? '',
                            imageSize: 52.r,
                          ),
                          4.hBox,
                          SizedBox(
                            width: 52.w,
                            child: Text(
                              _formatTotal(supporter.total ?? '0'),
                              style: TextStyle(
                                color: ColorManager.secondaryText,
                                fontSize: 10.sp,
                              ),
                              textAlign: TextAlign.center,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                    );
                  },
                ),
              ),
            );
          },
        ),
        16.hBox,

        // CP section
        _SectionTitle(
          title: StringManager.cP.tr(),
          color: ColorManager.primary,
        ),
        8.hBox,
        Theme2VisitorCpCard(
          myProfile: Methods.isMe(user?.id.toString() ?? ''),
          userEntity: user ?? const UserEntity(),
          cpProfileBloc: cpProfileBloc,
        ),
        16.hBox,
      ],
    );
  }

  String _formatTotal(String total) {
    final num = double.tryParse(total) ?? 0;
    if (num >= 1000000) return '${(num / 1000000).toStringAsFixed(1)}m';
    if (num >= 1000) return '${(num / 1000).toStringAsFixed(0)}k';
    return total;
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  final Color color;

  const _SectionTitle({required this.title, required this.color});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Container(
          width: 4.w,
          height: 18.h,
          decoration: BoxDecoration(
            color: color,
            borderRadius: BorderRadius.circular(2.r),
          ),
        ),
        8.wBox,
        Text(
          title,
          style: TextStyle(
            color: ColorManager.textPrimary,
            fontSize: 16.sp,
            fontWeight: FontWeight.w700,
          ),
        ),
      ],
    );
  }
}

class _AgencyCard extends StatelessWidget {
  final String title;
  final String image;
  final String memberCount;
  final List<Color> backgroundGradient;
  final VoidCallback onTap;

  const _AgencyCard({
    required this.title,
    required this.image,
    required this.memberCount,
    required this.backgroundGradient,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: EdgeInsets.symmetric(horizontal: 16.w, vertical: 14.h),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: backgroundGradient,
            begin: Alignment.centerRight,
            end: Alignment.centerLeft,
          ),
          borderRadius: BorderRadius.circular(14.r),
        ),
        child: Row(
          children: [
            // Arrow
            Icon(Icons.arrow_back_ios,
                color: ColorManager.white.withValues(alpha: 0.7), size: 16.sp),
            const Spacer(),
            // Info
            Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                Text(
                  title,
                  style: TextStyle(
                    color: ColorManager.onDark,
                    fontSize: 15.sp,
                    fontWeight: FontWeight.w600,
                  ),
                ),
                if (memberCount.isNotEmpty) ...[
                  4.hBox,
                  Row(
                    children: [
                      Text(
                        memberCount,
                        style: TextStyle(
                          color: ColorManager.onDark.withValues(alpha: 0.8),
                          fontSize: 12.sp,
                        ),
                      ),
                      4.wBox,
                      Icon(Icons.people_outline,
                          color: ColorManager.white.withValues(alpha: 0.8),
                          size: 14.sp),
                    ],
                  ),
                ],
              ],
            ),
            12.wBox,
            // Image
            ClipRRect(
              borderRadius: BorderRadius.circular(10.r),
              child: ImageViewWidget(
                url: EndPoints.getImage(image),
                displayName: title,
                height: 55.h,
                width: 55.w,
                boxFit: BoxFit.cover,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
